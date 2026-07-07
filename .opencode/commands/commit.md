---
description: Stage changes and create a conventional commit in English. Run this when the user says "commit" or wants to create a git commit.
---

Create a git commit following **Conventional Commits** format (`type(scope): description`), written in **English**.

1. Run `git status` and `git diff` to understand the current changes.
2. If nothing is staged, show the user what files were modified/deleted/created and ask if they want to stage **all** or **specific files**.
3. **Before committing**, sort all import statements alphabetically in every `.vue` and `.ts` file that was modified. Group them: external packages first, then relative imports (`@/` or `./`). Within each group, sort by the import source path alphabetically.
4. Once files are staged, ask the user for the **commit type** (feat, fix, chore, refactor, docs, style, test, perf, ci, build, revert) and **scope** (optional). Suggest these based on the diff.
5. Generate a concise commit message in English following the Conventional Commits spec.
6. Show the proposed message to the user and ask for confirmation before committing.
7. Run `git commit -m "type(scope): description"` to execute.

If `$ARGUMENTS` is provided, use it as the commit message directly (skip steps 3-4, just confirm and commit).

Do NOT use `-m` with multiline strings. If a body is needed, use `git commit` without `-m` so the editor opens.
