---
description: Review a branch diff for logic, style, performance, and naming issues
---

Review the branch `$1` by comparing it against a base branch.

If a second argument is provided (`/review feature/x main`), use it as the base. Otherwise, default to `main`.

Context:
- Base branch: use `$2` if provided, otherwise `main`
- Diff against base: !`git diff ${2:-main}..$1 --stat`
- Full diff: !`git diff ${2:-main}..$1`

Analyze the diff for:
- **Logic errors**: off-by-one, incorrect conditions, missing early returns, wrong operator usage
- **Unhandled errors**: missing try/catch, unvalidated input, missing authorization checks
- **Performance issues**: N+1 queries, missing eager loading, unnecessary loops, heavy operations inside loops
- **Naming inconsistencies**: variable/method names that don't match conventions (descriptive names, camelCase for JS/PHP, PascalCase for classes)
- **Style violations**: check against Laravel Pint conventions and existing code patterns in the project
- **Architecture**: following existing patterns (Action classes, service layer, etc.)

Guidelines:
1. Only comment on issues that are **truly relevant** — don't nitpick
2. Prioritize correctness and security over style preferences
3. If unsure about something, ask rather than flagging as a defect
4. Provide a summary of findings at the end
5. If the diff is clean, just say the code looks good
