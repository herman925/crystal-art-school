#!/usr/bin/env python3
import argparse
import datetime as dt
import fcntl
import json
import locale
import os
import re
import signal
import stat
import sys
import time
import uuid

CHUNK = 65536
PREFIX = 104  # Up to 26 Unicode header characters, without transforming retained bytes.
TRIGGER = 1048576
BASELINE = "740362ceb846d2a68490ff4f9accd191d52dc1f18390171eb6b8fd9857d756e2"
LOGS = (("php", "php-errors.log", 180, 0o644), ("audit", "audit.log", 90, 0o600))
HEADERS = {
    "php": (re.compile(r"^\[(\d{2}-[A-Za-z]{3}-\d{4} \d{2}:\d{2}:\d{2})(?: UTC)?\]"), "%d-%b-%Y %H:%M:%S"),
    "audit": (re.compile(r"^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}) UTC \|"), "%Y-%m-%d %H:%M:%S"),
}
GENERATION = re.compile(r"cas-retention-[0-9a-f]{32}\Z")
LEGACY = re.compile(r"php-errors\.log\.[^/]+\Z")


class RetentionError(Exception):
    pass


class Budget:
    def __init__(self, max_bytes, space, seconds):
        self.remaining = max_bytes
        self.space = space - 1048576
        self.deadline = time.monotonic() + seconds

    def check(self):
        if time.monotonic() >= self.deadline:
            raise RetentionError("TIME_BUDGET: input preserved")

    def admit(self, size):
        self.check()
        if size > self.remaining or size > self.space:
            raise RetentionError("BYTE_OR_SPACE_BUDGET: refused before reading")
        self.remaining -= size
        self.space -= size


def keep_header(prefix, kind, cutoff, keeping):
    pattern, fmt = HEADERS[kind]
    match = pattern.match(prefix.decode("utf-8", errors="replace"))
    if not match:
        return keeping, "continuation"
    try:
        return dt.datetime.strptime(match[1], fmt) >= cutoff, "dated"
    except ValueError:
        return True, "invalid_date"


def filter_bytes(source, target, size, kind, cutoff, budget):
    prefix = bytearray()
    keeping, decided, after_cr = True, False, False
    counts = dict(read=0, kept=0, dropped=0, dated=0, invalid_date=0, continuation=0)

    def write(data):
        if data and target.write(data) != len(data):
            raise RetentionError("SHORT_WRITE: input preserved")

    def decide():
        nonlocal keeping, decided
        keeping, category = keep_header(bytes(prefix), kind, cutoff, keeping)
        counts[category] += 1
        decided = True
        if keeping:
            write(prefix)
            counts["kept"] += len(prefix)
        else:
            counts["dropped"] += len(prefix)
        prefix.clear()

    while counts["read"] < size:
        budget.check()
        chunk = source.read(min(CHUNK, size - counts["read"]))
        if not chunk:
            raise RetentionError("SHORT_READ: input preserved")
        counts["read"] += len(chunk)
        start = 0
        if after_cr:
            after_cr = False
            if chunk.startswith(b"\n"):
                if keeping:
                    write(b"\n")
                    counts["kept"] += 1
                else:
                    counts["dropped"] += 1
                start = 1
        cursor = start
        for delimiter in re.finditer(b"[\r\n]", chunk[start:]):
            stop = start + delimiter.start()
            if after_cr and chunk[stop:stop + 1] == b"\n" and stop == cursor:
                if keeping:
                    write(b"\n")
                    counts["kept"] += 1
                else:
                    counts["dropped"] += 1
                after_cr = False
                cursor = stop + 1
                continue
            after_cr = False
            segment = chunk[cursor:stop + 1]
            if not decided:
                take = min(PREFIX - len(prefix), len(segment))
                prefix.extend(segment[:take])
                segment = segment[take:]
                decide()
            if keeping:
                write(segment)
                counts["kept"] += len(segment)
            else:
                counts["dropped"] += len(segment)
            after_cr = chunk[stop] == 13
            decided = False
            cursor = stop + 1
        segment = chunk[cursor:]
        if segment:
            after_cr = False
            if not decided:
                take = min(PREFIX - len(prefix), len(segment))
                prefix.extend(segment[:take])
                segment = segment[take:]
                if len(prefix) == PREFIX:
                    decide()
            if decided:
                if keeping:
                    write(segment)
                    counts["kept"] += len(segment)
                else:
                    counts["dropped"] += len(segment)
    if prefix:
        decide()
    budget.check()
    if counts["kept"] + counts["dropped"] != size:
        raise RetentionError("ACCOUNTING: input preserved")
    return counts


def identity(info):
    return (info.st_dev, info.st_ino, info.st_size, info.st_mtime_ns, info.st_ctime_ns,
            info.st_uid, info.st_gid, stat.S_IMODE(info.st_mode), info.st_nlink)


def checked_file(fd, mode):
    info = os.fstat(fd)
    if not stat.S_ISREG(info.st_mode) or info.st_uid != os.getuid() or info.st_nlink != 1:
        raise RetentionError("SCOPE: unexpected file type, owner or links")
    if stat.S_IMODE(info.st_mode) != mode or os.listxattr(fd):
        raise RetentionError("SCOPE: mode or extended metadata needs operator review")
    return info


def private_directory(path=None, name=None, parent=None):
    fd = os.open(path if path is not None else name,
                 os.O_RDONLY | os.O_DIRECTORY | os.O_NOFOLLOW, dir_fd=parent)
    info = os.fstat(fd)
    if info.st_uid != os.getuid() or stat.S_IMODE(info.st_mode) != 0o700 or os.listxattr(fd):
        os.close(fd)
        raise RetentionError("SCOPE: directory must be owned by this user and mode 0700")
    return fd


def write_receipt(directory, receipt):
    name = "receipt-" + uuid.uuid4().hex + ".json"
    fd = os.open(name, os.O_WRONLY | os.O_CREAT | os.O_EXCL | os.O_NOFOLLOW, 0o600, dir_fd=directory)
    with os.fdopen(fd, "wb") as output:
        payload = (json.dumps(receipt, sort_keys=True) + "\n").encode()
        if output.write(payload) != len(payload):
            raise RetentionError("RECEIPT_SHORT_WRITE")
        output.flush()
        os.fsync(output.fileno())
    os.fsync(directory)


def capture(root, generation, kind, name, days, mode, now, budget):
    result = dict(log=kind, state="RETENTION_EXCEPTION", writer_closure="UNPROVEN",
                  retention_days=days, cutoff=(now - dt.timedelta(days=days)).isoformat(),
                  source_retired=False, output_published=False)
    try:
        source_fd = os.open(name, os.O_RDONLY | os.O_NOFOLLOW | os.O_NONBLOCK, dir_fd=root)
    except FileNotFoundError:
        return dict(log=kind, state="ABSENT")
    with os.fdopen(source_fd, "rb", buffering=0) as source:
        before = checked_file(source.fileno(), mode)
        if before.st_size == 0:
            return dict(log=kind, state="EMPTY", source_retired=False)
        budget.admit(before.st_size)
        available = os.fstatvfs(generation).f_bavail * os.fstatvfs(generation).f_frsize
        if available < before.st_size + 1048576:
            raise RetentionError("FILESYSTEM_SPACE: input not detached")
        result["admitted_identity"] = identity(before)
        write_receipt(generation, result)
        current = os.stat(name, dir_fd=root, follow_symlinks=False)
        if identity(current) != identity(before):
            raise RetentionError("CHANGED_BEFORE_HANDOFF: no mutation")
        raw = kind + ".raw"
        os.rename(name, raw, src_dir_fd=root, dst_dir_fd=generation)
        os.fsync(generation)
        os.fsync(root)
        result["raw"] = raw
        # Only append/open-create writers are supported; namespace mutations are not certified.
        try:
            active_fd = os.open(name, os.O_WRONLY | os.O_CREAT | os.O_EXCL | os.O_NOFOLLOW,
                                mode, dir_fd=root)
        except FileExistsError:
            active_fd = os.open(name, os.O_RDONLY | os.O_NOFOLLOW | os.O_NONBLOCK, dir_fd=root)
            with os.fdopen(active_fd, "rb", buffering=0) as active:
                new = checked_file(active.fileno(), mode)
                if (new.st_dev, new.st_ino) == (before.st_dev, before.st_ino) or new.st_gid != before.st_gid:
                    raise RetentionError("SCOPE: competing active creation needs review; raw preserved")
        else:
            with os.fdopen(active_fd, "wb", buffering=0) as active:
                os.fchown(active.fileno(), before.st_uid, before.st_gid)
                os.fchmod(active.fileno(), mode)
                os.fsync(active.fileno())
                new = checked_file(active.fileno(), mode)
        os.fsync(root)
        result["active_identity"] = identity(new)
        detached = checked_file(source.fileno(), mode)
        if (detached.st_dev, detached.st_ino, detached.st_size, detached.st_mtime_ns, detached.st_gid) != (
                before.st_dev, before.st_ino, before.st_size, before.st_mtime_ns, before.st_gid):
            raise RetentionError("CHANGED_DURING_HANDOFF: raw preserved")
        if identity(os.stat(raw, dir_fd=generation, follow_symlinks=False)) != identity(detached):
            raise RetentionError("SCOPE: detached pathname changed; input preserved")
        stage = kind + ".candidate"
        output_fd = os.open(stage, os.O_WRONLY | os.O_CREAT | os.O_EXCL | os.O_NOFOLLOW,
                            0o600, dir_fd=generation)
        with os.fdopen(output_fd, "wb", buffering=0) as output:
            try:
                result["counts"] = filter_bytes(source, output, before.st_size, kind,
                                               now - dt.timedelta(days=days), budget)
                os.fsync(output.fileno())
                result["output_identity"] = identity(os.fstat(output.fileno()))
                result["php_retained_trigger_reached"] = kind == "php" and result["counts"]["kept"] >= TRIGGER
                result["source_changed"] = identity(os.fstat(source.fileno())) != identity(detached)
                result["candidate"] = stage
            except BaseException:
                # Purge only this invocation's exact, still-open private partial output, never raw/incident files.
                if identity(os.stat(stage, dir_fd=generation, follow_symlinks=False)) == identity(os.fstat(output.fileno())):
                    os.unlink(stage, dir_fd=generation)
                raise
        os.fsync(generation)
    result["exception"] = "Raw generation may still receive writes; candidate is NOT a published replacement. Operator must certify closure and separately approve retirement. No space reclaimed."
    return result


def timeout(signum, frame):
    raise RetentionError("TIME_BUDGET: preserve raw and inspect private receipt")


def run(args):
    locale.setlocale(locale.LC_TIME, "C")
    budget = Budget(args.max_bytes, args.space_budget, args.max_seconds)
    now = dt.datetime.now(dt.timezone.utc).replace(tzinfo=None)
    root = private_directory(path=args.directory)
    lock = generation = None
    try:
        lock = os.open(".cas-retention.lock", os.O_RDWR | os.O_CREAT | os.O_NOFOLLOW | os.O_NONBLOCK,
                       0o600, dir_fd=root)
        checked_file(lock, 0o600)
        fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
        held = []
        with os.scandir(root) as entries:
            for index, entry in enumerate(entries):
                budget.check()
                if index >= 256:
                    raise RetentionError("SCOPE: directory inventory budget exceeded")
                if GENERATION.fullmatch(entry.name) or LEGACY.fullmatch(entry.name):
                    held.append(entry.name)
        pending = False
        for kind, name, days, mode in LOGS:
            try:
                fd = os.open(name, os.O_RDONLY | os.O_NOFOLLOW | os.O_NONBLOCK, dir_fd=root)
                with os.fdopen(fd, "rb", buffering=0) as source:
                    pending |= checked_file(source.fileno(), mode).st_size > 0
            except FileNotFoundError:
                continue
            except (OSError, RetentionError):
                pending = True
        if not pending:
            state = "RETENTION_EXCEPTION" if held else "NO_DATA_TO_PRUNE"
            print(json.dumps(dict(state=state, previous_holds=held, source_retired=False)))
            return 3 if held else 0
        token = "cas-retention-" + uuid.uuid4().hex
        os.mkdir(token, mode=0o700, dir_fd=root)
        generation = private_directory(name=token, parent=root)
        receipt = dict(version=1, baseline_sha256=BASELINE, run=token, started=now.isoformat(),
                       state="RETENTION_EXCEPTION", previous_holds=held, logs=[],
                       byte_budget=args.max_bytes, space_budget=args.space_budget,
                       seconds_budget=args.max_seconds, original_purge=False)
        write_receipt(generation, receipt)
        for kind, name, days, mode in LOGS:
            try:
                result = capture(root, generation, kind, name, days, mode, now, budget)
            except (OSError, RetentionError) as error:
                result = dict(log=kind, state="RETENTION_EXCEPTION", error=str(error), source_retired=False)
            receipt["logs"].append(result)
            write_receipt(generation, receipt)
        exceptions = held or any(row["state"] not in ("ABSENT", "EMPTY") for row in receipt["logs"])
        receipt["state"] = "RETENTION_EXCEPTION" if exceptions else "NO_DATA_TO_PRUNE"
        write_receipt(generation, receipt)
        print(json.dumps(dict(state=receipt["state"], run=token, source_retired=False)))
        return 3 if exceptions else 0
    finally:
        if generation is not None:
            os.close(generation)
        if lock is not None:
            os.close(lock)
        os.close(root)


def main():
    parser = argparse.ArgumentParser(description="Safety-first detached retention candidates; never retire unproven generations")
    parser.add_argument("--directory", required=True)
    parser.add_argument("--space-budget", type=int, required=True,
                        help="operator-verified account allocation for THIS run, not filesystem free space")
    parser.add_argument("--max-bytes", type=int, default=64 * 1024 * 1024)
    parser.add_argument("--max-seconds", type=float, default=30)
    args = parser.parse_args()
    if not (0 < args.max_bytes <= 256 * 1024 * 1024 and 0 < args.space_budget <= 256 * 1024 * 1024
            and 0 < args.max_seconds <= 120):
        parser.error("budgets must be positive; bytes/space <=256MiB, seconds <=120")
    signal.signal(signal.SIGALRM, timeout)
    signal.setitimer(signal.ITIMER_REAL, args.max_seconds)
    try:
        return run(args)
    except (OSError, RetentionError) as error:
        print("RETENTION_EXCEPTION: " + str(error), file=sys.stderr)
        return 3
    finally:
        signal.setitimer(signal.ITIMER_REAL, 0)


if __name__ == "__main__":
    sys.exit(main())
