# Repository scope

All work, changes and new files for RP Custom Scrollbar belong in `umatstar-lab/RP-customs-crollbar`, as requested by the owner.

The repository root contains the Joomla plugin source. `dist/` contains installable packages and their checksums. `docs/` contains verification notes. Build packages with `python3 tools/build.py`; do not include tests, development dependencies or the original 1.0.0 archive in the installable ZIP.

Preserve the extension identity `system/rpcustomscrollbar` and existing parameter names during upgrades. Validate settings on save and again before CSS output. Record the actual Joomla/PHP versions tested and distinguish standalone tests from full CMS integration checks.

Keep `CHANGELOG.md` both in the repository and in every installable ZIP. For every new version, add a versioned entry describing all changes; preserve the existing version history. This is an explicit owner requirement.
