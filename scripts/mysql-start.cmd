@echo off
rem Starts the local MySQL 8.4 dev server (port 3307, data in C:\mysql84\data).
rem XAMPP MariaDB keeps port 3306; this project uses 3307 (see .env).
start "MySQL 8.4 (3307)" /min "C:\Program Files\MySQL\MySQL Server 8.4\bin\mysqld.exe" --defaults-file=C:\mysql84\my.ini --console
