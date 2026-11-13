## 1. Project Title
**Local Water Collection & Delivery Management System**  


## 2. Description
The **Local Water Collection & Delivery Management System (WCDMS)** is a full-stack, mobile-responsive web application for local water refilling stations. It was developed around the operations of **Majestic Spring**, a refilling station in Caloocan City, and would serve as a replacement to traditional means of operating the business such as handwritten logbooks and text-message ordering with a single online portal for household customers and station staff. The system aims to address common problems faced by both regular customers and water station staff such as delayed deliveries, untracked orders, misplaced records, and difficulties managing daily operations. Customers can schedule pickups and deliveries, purchase new containers, view prices and digital receipts, and track their orders. Meanwhile, staff can manage customer and employee records, organize orders, monitor delivery progress, record payments, and generate sales reports.

## 3. Features

**Customer Portal**

Customer Dashboard: Displays instructions for ordering water refill or new container as well as view current order status.

Order Scheduling: Allows customers to schedule water refills and deliveries based on available time slots.

Repeat Orders: Saves usual refill preferences for faster reordering.

Container Purchase: Supports the purchase of new 5-gallon Slim and Round containers.

Order Tracking: Displays delivery progress, digital receipts, and viewing of past orders.

**Admin / Staff Portal**

Operations Dashboard: Displays active orders, delivery schedules, and daily sales.

Account and Product Management: Allows staff to manage customer and employee accounts, container products, and prices.

Order Queue Management: Prioritizes walk-in orders and carries over unfinished deliveries to the next day's batch if unfinished within the day.

Payment Monitoring: Records customer payments and tracks unpaid balances.

Sales Reports and Analytics: Provides sales charts and printable daily sales report.

## 4. Technologies
**Front-End:**
-HTML5
-CSS3
-JavaScript (ES6+)
-Chart.js

**Back-End:**
-PHP 8+
-PHP Data Objects (PDO)

**Database:**
-MySQL / MariaDB (majestic_spring_wcdms)

**Development Environment & Web Server:**
-XAMPP
-Apache HTTP Server

**Version Control:**
-Git / GitHub

**Deployment & Hosting:**
-InfinityFree Cloud Hosting


## 5. Installation Instructions
**(Local XAMPP Setup)**
 **Step1: Install Prerequisites**: Download and install [XAMPP](https://www.apachefriends.org/) (with Apache and MySQL) and [Git](https://git-scm.com/).

 **Step2: Clone the Repository**:
   cd C:/xampp/htdocs
   git clone https://github.com/<your-github-username>/majestic-spring-wcdms.git

**Step3: Start Local Services**: Open the **XAMPP Control Panel** and start **Apache** and **MySQL**.

**Step4: Configure Database Connection**: Verify that the local database connection settings in `admin_db1.php`–`admin_db4.php`, `cust_db1.php`–`cust_db3.php`, `Daily_Sales_Report.php`, and `Customer_Digital_Receipt.php` point to your local XAMPP MySQL instance:

   ```php
   $dbHost = '127.0.0.1';
   $dbPort = '3306';
   $dbName = 'majestic_spring_wcdms';
   $dbUser = 'root';
   $dbPass = '';
   ```
**Step5: Import Database**: Open your browser and navigate to  (http://localhost/phpmyadmin), download and import majestic_spring_wcdms.sql unto phpmyadmin.

   
**Step6: Launch the Application**: Open your browser and navigate to:
   ```
   http://localhost/majestic-spring-wcdms/
   ```

## 6. Database Setup
1. Open **phpMyAdmin** at (http://localhost/phpmyadmin).
2. Create a new database named **`majestic_spring_wcdms`** 
3. Select the `majestic_spring_wcdms` database, click the **Import** tab, choose `database/database.sql`, and click **Import** to create all relational tables (`customer`, `customer_cp_num`, `employee`, `employee_cp_num`, `container_product`, `time_slot`, `order_status`, `order_information`, `order_item`, and `payment`).

## 7. Screenshots
See Screenshots Folder

## 8. Live Demo
- **Deployed Application URL**: [http://majesticspring-wcdms.free.nf/](https://www.google.com/search?q=http://majesticspring-wcdms.free.nf/)

## 9. Developer
- **Clark Louise Cheng Pinlac** — Full-Stack Web Developer (System Architecture, UI/UX & Responsive Styling, PHP/PDO Backend & MySQL Database Integration, and Cloud Deployment)
