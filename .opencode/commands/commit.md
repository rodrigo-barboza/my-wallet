---
description: Commit changes with conventional commits in English, grouped by related files
---

Analyze the current git state and make commits using conventional commit format.

Context:
- Git status: !`git status`
- Git diff summary: !`git diff --stat`

Group files into logical commits following these conventions:
- **feat**: new features (e.g., `feat: add user registration form`)
- **fix**: bug fixes (e.g., `fix: resolve date parsing in purchases`)
- **docs**: documentation changes (e.g., `docs: update AGENTS.md with UI guidelines`)
- **chore**: maintenance tasks (e.g., `chore: add shadcn dialog component`)
- **refactor**: code refactoring (e.g., `refactor: extract card footer into component`)
- **style**: formatting/style changes (e.g., `style: format Register.vue indentation`)
- **test**: test additions/modifications (e.g., `test: add Register page assertions`)

Rules:
1. Group only related files together in the same commit — never lump unrelated changes
2. Each commit must make sense independently
3. Commit messages in English only, imperative mood, no period at end
4. For each group, run: `git add <files>` then `git commit -m "<type>: <message>"`
5. Process groups sequentially (one commit per step), stopping after each
6. Ask me before making a commit that touches 4+ files — it might be too large
7. If there's only one file changed, one commit is fine
8. NEVER run `git push` — this command is for committing only
