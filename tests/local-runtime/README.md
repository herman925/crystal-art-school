# cas-local: synthetic WordPress runtime (local only)

Loopback only. Mail never leaves the stack: `wp_mail()` goes to the compose-internal Mailpit, and `sendmail_path` is `/bin/false`.

| Item | Value |
|---|---|
| WordPress | http://127.0.0.1:18089/ |
| Mailpit UI and API | http://127.0.0.1:18026/ |
| Database | `db` service, no published port |

Start (from any directory):

    docker compose --env-file ~/cas-local-runtime/cas-local.env -p cas-local -f tests/local-runtime/docker-compose.yml up -d

The env file holds the local database passwords. Keep it mode 600, outside the repo, never committed. Volumes `cas-local_dbdata` and `cas-local_wpdata` are kept across restarts. Do not run `docker volume prune` or `docker system prune` on this machine.

Theme: this checkout's theme by default. To test another revision, set `CAS_CHILD_THEME_SRC` to that theme folder, and set it on every compose command in the same shell.
