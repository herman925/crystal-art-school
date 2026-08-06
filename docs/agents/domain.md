# Domain docs

**Single-context.** One `CONTEXT.md` at the repo root, ADRs in `docs/adr/`.

## Consumer rules

- Read `CONTEXT.md` before making structural changes.
- Read any ADR in `docs/adr/` that touches the area being changed, and respect it.
- `docs/adr/0001-cms-editable-whitelist.md` applies to every page issue in the
  2026-08 round: nothing is client-editable unless its section names it.
- The authoritative design specification for the 2026-08 round is
  `docs/20260804_Design_Direction.md`, with `docs/20260806_SPEC_design_round_2.md` as its
  implementation spec. **Both are gitignored** (client working material, public repo) —
  they exist locally only. Cite them by section number.
