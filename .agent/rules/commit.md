---
trigger: always_on
---

COMMIT DISCIPLINE (MANDATORY)

For every completed change (fix, feature, refactor, chore):

1. You MUST create a git commit immediately after finishing the change.
2. Commits must be ATOMIC:
   - One commit = one logical change.
   - Do NOT mix unrelated changes in a single commit.
3. If a task consists of multiple logical steps, split it into multiple commits.

Commit message rules:
- Use conventional commits format:
  - feat: for new features
  - fix: for bug fixes
  - refactor: for refactoring without behavior change
  - chore: for tooling/config changes
- Message must clearly describe the change.
- Examples:
  - feat(auction): add auto-bid proxy logic
  - fix(bidding): prevent race condition on concurrent bids
  - chore(i18n): add missing auction translation keys

Workflow enforcement:
- After each commit, STOP and WAIT for next instruction.
- Do NOT continue implementing additional features without a commit.
- Never leave multiple unrelated files unstaged or uncommitted.

If multiple files are changed:
- Verify they all belong to the same logical change.
- If not, split into multiple commits.

Before continuing:
- Ensure working tree is clean (git status shows no pending changes).