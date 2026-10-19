HOSTEL MANAGEMENT SYSTEM — PHP + MYSQL

REQUIREMENTS
- XAMPP (Apache and MySQL)
- A modern browser

INSTALLATION
1. Extract the hostel_management folder into C:\xampp\htdocs\
2. Open XAMPP Control Panel and start Apache and MySQL.
3. Visit http://localhost/phpmyadmin
4. Choose Import, select database.sql from this folder, and click Import.
   Alternatively, open the SQL tab and paste the contents of database.sql.
5. If your MySQL username/password differs from the XAMPP default, update db.php.
   Default settings in db.php: host=localhost, username=root, password blank.
6. Visit http://localhost/hostel_management/

FEATURES
- Dashboard summary and room occupancy
- Student CRUD and search
- Room CRUD and search
- Room allocation and release
- Fee record CRUD and search
- Prepared SQL statements, HTML escaping, basic validation and CSRF tokens

NOTES
- This is an educational mini-project starter, not a production hostel system.
- It intentionally does not include a login system.
- Student/room records with linked allocation or fee history cannot be deleted; this avoids broken references.
- The pending fee number on the dashboard sums records marked Pending or Partial. If you need balance calculations, add a separate total-fee/paid-amount model.
