# CI workflow maintenance

The local reusable workflows are adapted from [humhub/module-coding-standards](https://github.com/humhub/module-coding-standards/tree/e834750c27074b0e7d4c08ddbebf911bd26c5dfa) (MIT, declared in its composer.json), revision `e834750c27074b0e7d4c08ddbebf911bd26c5dfa`.

The base workflow sets PHP's web-server `max_execution_time=60` to match HumHub core CI. Cold SCSS compilation on develop exceeded the previous 30-second default and returned HTTP 500 before module tests. The server probe is bounded to 90 seconds, PHP server logs are retained on failure, and matrix failures do not cancel other versions. The develop wrapper preserves the upstream module-develop branch discovery.

Keep these two reusable workflow files synchronized across Gastromaster, Workplan and Sociolog. Review upstream changes before updating the snapshot. Once the timeout/logging fix is available upstream, these local copies can be replaced by a reviewed pinned upstream revision. They do not modify HumHub or any deployed installation.

Stable and develop remain separately named jobs. The stable job retains its existing upstream workflow; develop uses the corrected local workflow and still runs on pull requests, main pushes, schedules and manual dispatch.
