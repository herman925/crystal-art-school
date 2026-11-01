#!/usr/bin/env python3
"""Install a reviewed guide release, preserving readers and checking exact deployment preimages."""
import argparse
import hashlib
import json
import os
from pathlib import Path, PurePosixPath
import shutil
import subprocess
import tarfile
import uuid

HANDLERS = ('auth.php', 'gate.php', 'login.php', 'logout.php', '.htaccess')


def digest(path):
    return hashlib.sha256(path.read_bytes()).hexdigest()


def tree(path):
    result = {}
    if not path.is_dir() or path.is_symlink():
        raise RuntimeError('Missing or linked private build directory')
    for item in sorted(path.rglob('*')):
        if item.is_symlink():
            raise RuntimeError('Symlink in private build')
        if item.is_file():
            result[item.relative_to(path).as_posix()] = digest(item)
        elif not item.is_dir():
            raise RuntimeError('Unsupported private build entry')
    return result


def snapshot(target):
    return {'files': {name: digest(target / name) for name in (*HANDLERS, 'users.php')},
            'private': tree(target / '_private')}


def unpack(archive, destination):
    destination.mkdir(mode=0o700)
    total = 0
    with tarfile.open(archive, 'r:gz') as bundle:
        for member in bundle:
            rel = PurePosixPath(member.name)
            parts = rel.parts
            if not parts: continue
            if rel.is_absolute() or '..' in parts or (parts[0] not in HANDLERS and parts[0] != '_private'):
                raise RuntimeError('Unexpected release path')
            if not (member.isdir() or member.isfile()):
                raise RuntimeError('Release links and special files are forbidden')
            if member.size < 0 or member.size > 8 * 1024 * 1024:
                raise RuntimeError('Release file exceeds bound')
            total += member.size
            if total > 64 * 1024 * 1024:
                raise RuntimeError('Release exceeds bound')
            path = destination.joinpath(*parts)
            path.parent.mkdir(parents=True, exist_ok=True)
            if member.isdir():
                path.mkdir(exist_ok=True)
            else:
                with bundle.extractfile(member) as source, path.open('xb') as output:
                    shutil.copyfileobj(source, output, 65536)
                path.chmod(0o644)


def atomic_copy(source, destination, mode):
    temporary = destination.with_name(destination.name + '.guide-' + uuid.uuid4().hex)
    with temporary.open('xb') as output:
        output.write(source.read_bytes())
    temporary.chmod(mode)
    os.replace(temporary, destination)


def install(stage, target, backup, expected, lint=True):
    if target.resolve() in backup.resolve().parents or backup.resolve() == target.resolve():
        raise RuntimeError('Guide backups must be outside the public target')
    if (stage / 'users.php').exists():
        raise RuntimeError('Reader credentials must never be included')
    for name in HANDLERS:
        if not (stage / name).is_file() or (stage / name).is_symlink():
            raise RuntimeError('Missing/linked guide handler')
    for audience in ('crystal', 'admin'):
        if not (stage / '_private' / audience / 'index.html').is_file():
            raise RuntimeError('Both audience builds are required')
    if (stage / '_private/.htaccess').read_text().strip() != 'Require all denied':
        raise RuntimeError('Private builds must deny direct serving')
    new_tree = tree(stage / '_private')
    if lint:
        for name in HANDLERS:
            if name.endswith('.php'):
                subprocess.run(['php', '-l', str(stage / name)], check=True, capture_output=True)
    if snapshot(target) != expected:
        raise RuntimeError('Deployed guide changed; nothing installed')
    backup.mkdir(mode=0o700)
    modes = {}
    for name in HANDLERS:
        shutil.copy2(target / name, backup / name)
        modes[name] = (target / name).stat().st_mode & 0o777
    applied = []
    old_moved = False
    new_moved = False
    try:
        # Install the account-aware gate BEFORE administrator files become addressable.
        for name in HANDLERS:
            if digest(target / name) != expected['files'][name]:
                raise RuntimeError('Concurrent guide handler update')
            atomic_copy(stage / name, target / name, modes[name])
            applied.append(name)
        if tree(target / '_private') != expected['private']:
            raise RuntimeError('Concurrent guide content update')
        os.replace(target / '_private', backup / '_private')
        old_moved = True
        os.replace(stage / '_private', target / '_private')
        new_moved = True
        if tree(target / '_private') != new_tree or digest(target / 'users.php') != expected['files']['users.php']:
            raise RuntimeError('Guide/reader readback mismatch')
        for name in HANDLERS:
            if digest(target / name) != digest(stage / name):
                raise RuntimeError('Handler readback mismatch')
    except Exception:
        # Restore the old tree before restoring its broader legacy gate.
        if new_moved:
            if tree(target / '_private') != new_tree:
                raise RuntimeError('Rollback stopped: private build changed concurrently')
            os.replace(target / '_private', stage / '_failed-private')
        if old_moved:
            os.replace(backup / '_private', target / '_private')
        for name in ('login.php', 'logout.php', '.htaccess', 'gate.php', 'auth.php'):
            if name in applied and digest(target / name) == digest(stage / name):
                atomic_copy(backup / name, target / name, modes[name])
        raise
    return {'backup': str(backup), 'private_files': len(new_tree), 'reader_credentials_preserved': True,
            'handlers': {name: digest(target / name) for name in HANDLERS}}


def main():
    p = argparse.ArgumentParser()
    p.add_argument('action', choices=['snapshot', 'install'])
    p.add_argument('--target', required=True, type=Path)
    p.add_argument('--archive', type=Path)
    p.add_argument('--expected', type=Path)
    p.add_argument('--backup', type=Path)
    args = p.parse_args()
    if args.action == 'snapshot':
        result = snapshot(args.target)
    else:
        if not all([args.archive, args.expected, args.backup]):
            p.error('install requires archive, expected and backup')
        stage = args.archive.parent / ('payload-' + uuid.uuid4().hex)
        unpack(args.archive, stage)
        result = install(stage, args.target, args.backup, json.loads(args.expected.read_text()))
    print(json.dumps(result))

if __name__ == '__main__': main()
