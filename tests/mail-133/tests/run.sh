#!/bin/sh
# Reset the test site, then run the suite.
cd /var/www/html
wp db reset --yes >/dev/null 2>&1
wp core install --url=http://localhost:8089 --title="Crystal Art School" --admin_user=cas_admin --admin_password=pw --admin_email=admin@crystalau.test --skip-email >/dev/null 2>&1
wp plugin activate tutor >/dev/null 2>&1
rm -f /tmp/audit.log
curl -s -X DELETE http://mailpit:8025/api/v1/messages >/dev/null
wp eval-file /tests/run.php 2>&1 | grep -v "wp_tutor_order_itemmeta\|^[[:space:]]*\(FROM\|WHERE\|ORDER\|LIMIT\)"
