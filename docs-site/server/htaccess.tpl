DirectoryIndex disabled
Options -Indexes

<Files "auth.php">
  Require all denied
</Files>
<Files "users.php">
  Require all denied
</Files>

RewriteEngine On

# The login and logout pages answer for themselves; everything else goes to the gate.
RewriteRule ^(login|logout)\.php$ - [L]
RewriteCond %{REQUEST_URI} !^/gate\.php$
RewriteRule ^ gate.php [L,QSA]
