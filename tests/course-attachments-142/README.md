# Course page "What comes with it": local test (issue #142)

Loads `inc/cas-courses.php` and `inc/cas-course-single.php` as they are into a throwaway WordPress with
Tutor 4.1.0 (free) and renders the real course markup for four courses: no extras, text lines only,
attachments only, both. Needs Docker. Nothing touches the live site.

```sh
docker compose up -d db wp
docker compose run --rm cli sh -c 'cd /var/www/html && wp core install --url=http://localhost:8089 --title=T --admin_user=a --admin_password=p --admin_email=a@example.test --skip-email && wp plugin install https://downloads.wordpress.org/plugin/tutor.4.1.0.zip --activate'
MSYS_NO_PATHCONV=1 docker compose run --rm cli sh /tests/run.sh   # resets the site, prints PASS/FAIL lines and a pass count
docker compose down -v
```

Mutation check: with the old `cas-course-single.php` the suite fails 7 of 14 checks (the placeholder shows and the section stays).
Not covered: the served page in a browser.
