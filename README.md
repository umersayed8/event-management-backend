# ⚙️ Evently Backend — Event Management Platform

The **Evently Backend** powers all the core functionalities of the Event Management Platform — handling **event creation**, **user authentication**, **database management**, and **server-side logic**.  
It’s built using **PHP** and **MySQL**, running locally via **XAMPP**.

---

## 🛠️ Tech Stack

- **Backend Language:** PHP  
- **Database:** MySQL  
- **Local Server:** XAMPP (Apache + MySQL)  
- **Development Tools:** phpMyAdmin  

---

## 🚀 Core Features

- **Event Management** — Create, edit, delete, and display event data.  
- **User Authentication** — Separate roles for organizers, sponsors, and attendees.  
- **Ticketing System** — Manage bookings, cancellations, and QR-based check-ins.  
- **Database Integration** — MySQL-based relational schema for smooth data handling.  
- **Admin Panel** — Control users, events, and reports from a central dashboard.  
- **Data Analytics (Future Scope)** — Generate insights on sales and attendance.  
- **Secure Payment Integration (Future Scope)** — Razorpay, PayPal, or Stripe.  

---

## 📁 Folder Structure

evently-backend/
│── config.php # Database connection setup
│── db/ # SQL schema and related queries
│ └── evently_db.sql # Database file for import
│── includes/ # Reusable backend logic (session, validation, etc.)
│── api/ # PHP APIs for frontend integration
│── admin/ # Admin module (manage users, events, etc.)
│── organizer/ # Organizer dashboard logic
│── sponsor/ # Sponsor module backend
│── ticket-buyer/ # Booking and payment backend logic
│── assets/ # Backend-related assets (if any)
│── index.php # Entry point or redirect to modules


---

## ⚙️ Local Setup Instructions (XAMPP)

### 1. Install & Run XAMPP
- Download from [https://www.apachefriends.org](https://www.apachefriends.org)
- Launch **XAMPP Control Panel** → Start **Apache** and **MySQL**

### 2. Place Backend Folder
Copy the project folder into:

C:/xampp/htdocs/evently

### 3. Create and Import Database
- Visit [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
- Create a new database, e.g. `evently_db`
- Import the SQL file:

db/evently_db.sql


### 4. Configure Database Connection
Edit the `config.php` file:

```php
<?php
$servername = "localhost";
$username   = "root";      // default for XAMPP
$password   = "";          // leave empty unless set
$dbname     = "evently_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
?>
```
### 5. Run Backend Locally
- Open in your browser:
- http://localhost/evently

### 🔒 Security & Future Enhancements
- Input validation and sanitization for SQL safety
- Password hashing using PHP’s password_hash()
- JWT-based authentication (future enhancement)
- Integration with payment APIs (Razorpay, Stripe, PayPal)
- Cloud deployment using Docker or hosting services

### 👨‍💻 Backend Developer
- Umer Sayed
- Backend Development (PHP, MySQL)
- Database Design & Integration
