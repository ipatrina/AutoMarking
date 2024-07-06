# AutoMarking

The AutoMarking system helps IT teachers create workflows that automatically mark coding assignments submitted by students.

This is a simple implementation of an automatic coding assignment grading system similar to Edstem that is suitable for HTML5, CSS, JavaScript, NodeJS and PHP teaching. You may implement other programming languages ​​based on this framework or using custom scripts.

![AutoMarking preview](https://thumbs2.imgbox.com/24/a2/DIiR3ZHF_t.png)


# System features

- Simple and intuitive user interface.

- SSO login design. Compatible with traditional login.

- User, course, assignment and testcase management.

- Integrated online code editor and preview in student submission workflow.

- Testcase-based auto-marking by regular expressions or custom scripts.

- Automatic grading and feedback of assignments/testcases for students.

- Report and analytics of assignment/testcase marking results for teachers.

- Optional email notification system.


# Operate environment

- PHP 8 and above.

- MariaDB 10 or MySQL 5 and above.


# Getting started

- Edit "config.php" and you are good to go.

- Users are divided into three permission levels: Supervisor, Coordinator and Student. The one and only Supervisor controls the entire system. Coordinators are to manage students, courses, assignments and testcases. Students must be invited to join courses.

- Although we encourage admins to use the modern SSO login method, we also provide traditional login and sign up channels as a demonstration. When you first try the system, open "sso_example.php" in the web browser and login with "supervisor/supervisor" for a fast entry.


# Open source

The system uses PHPMailer 6.8.0 to send user emails.

PHPMailer forked from: https://github.com/PHPMailer/PHPMailer
