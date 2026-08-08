# Domain docs

**Single-context.** One `CONTEXT.md` at the repo root, ADRs in `docs/adr/`.

## Consumer rules

- Read `CONTEXT.md` before making structural changes.
- Read any ADR in `docs/adr/` that touches the area being changed, and respect it.
- `docs/adr/0001-cms-editable-whitelist.md` applies to every page issue in the
  2026-08 round: nothing is client-editable unless its section names it.
- The authoritative design specification for the 2026-08 round is
  `docs/20260804_Design_Direction.md`. **It is gitignored** (client working material,
  public repo) — it exists locally only. Cite it by section number. The derived
  implementation spec that used to sit beside it was removed on 2026-08-09; the
  direction is the only spec now.
