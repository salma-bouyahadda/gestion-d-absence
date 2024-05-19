<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Management System</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(#000, rgb(102, 102, 200), #000);
        }
        .section {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 300px;
            margin: 10px;
        }
        button, input {
            padding: 10px;
            margin: 10px;
            width: calc(100% - 20px);
            border-radius: 5px;
        }
    </style>
</head>
<body>
    
    
    
    
    
    
    <?php
    session_start();

   


    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "attendance_system";
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    
    if (!isset($_SESSION['students'])) {
        $_SESSION['students'] = [];
    }


    
    $showAttendanceCode = false;
    $error= "";
    $error1="";
    $message = "";





    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_attendance'])) {
        $_SESSION['students'] = [];
        unset($_SESSION['attendanceFormVisible']);
        unset($_SESSION['attendanceCode']);
        $message = "Attendance list and session have been reset.";
    }
    



    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['generate_code'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    if ($email == 'prof@example.com' && $password == 'password') {
        $code = rand(1000, 9999);
        $_SESSION['attendanceCode'] = $code;
        $showAttendanceCode = true; 
    } else {
        $error = "Incorrect email or password.";
    }
}

    






if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['check_code'])) {
    $enteredCode = $_POST['code'];
    if ($enteredCode == $_SESSION['attendanceCode']) {
        $_SESSION['attendanceFormVisible'] = true;
    } else {
        $error1 = "Incorrect code.";
    }
}

    





    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_attendance'])) {
        $name = $_POST['name'];
        $surname = $_POST['surname'];
    
        $isDuplicate = false;
        foreach ($_SESSION['students'] as $student) {
            if ($student['name'] === $name && $student['surname'] === $surname) {
                $isDuplicate = true;
                $error1 = "Student with the same name and surname is already registered.";
                break;
            }
        }
    



        if (!$isDuplicate) {
           
            $students = $_SESSION['students'];
            $students[] = [
                'name' => $name,
                'surname' => $surname,
                'status' => 'Present',
                'timestamp' => date('Y-m-d H:i:s')
            ];
            $_SESSION['students'] = $students;
    
            
            $stmt = $conn->prepare("DELETE FROM absents WHERE name=? AND surname=?");
            $stmt->bind_param("ss", $name, $surname);
            $stmt->execute();
            $stmt->close();
    
        
            unset($_SESSION['attendanceFormVisible']); 
        }
    }
    
    








    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_data'])) {
        $students = $_SESSION['students'];
        foreach ($students as $student) {
            $name = $student['name'];
            $surname = $student['surname'];

            $stmt = $conn->prepare("SELECT * FROM registered_students WHERE name=? AND surname=?");
            $stmt->bind_param("ss", $name, $surname);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $stmt_insert = $conn->prepare("INSERT INTO students (name, surname) VALUES (?, ?)");
                $stmt_insert->bind_param("ss", $name, $surname);
                $stmt_insert->execute();
                $stmt_insert->close();
            }
            $stmt->close();
        }
        $message = "Data saved successfully!";
    }
    ?>







    <div id="teacher-section" class="section">
    <h1>Teacher Dashboard</h1>
    <?php if (!empty($error)): ?>
        <p style="color: red;"><?php echo $error; ?></p>
    <?php endif; ?>
    <form method="POST">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="generate_code">Generate Code</button>
            <button type="submit" name="reset_attendance">Reset Attendance List</button>
        </form>
        <?php if ($showAttendanceCode && isset($_SESSION['attendanceCode'])): ?>
            <div id="code-display">
                Your  code: <?php echo $_SESSION['attendanceCode']; ?>
            </div>
            <?php endif; ?>
            <button onclick="toggleAttendanceList()">Show/Hide Attendance List</button>
            <ul id="attendance-list" style="display: none;">
            <?php if (!empty($message)): ?>
    <div class="message">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

   <?php 
    if (isset($_SESSION['students'])) {
        foreach ($_SESSION['students'] as $student) {
            echo "<li>{$student['name']} {$student['surname']} - {$student['status']} - " . date('Y-m-d H:i:s', strtotime($student['timestamp'])) . "</li>";
        }
    } else {
        echo "<li>No attendance data available.</li>";
    }
    ?>
</ul>

        <form method="POST">
            <button type="submit" name="save_data">Save Data to Database</button>
        </form>
        <button onclick="toggleView('student')">Switch to Student View</button>
    </div>










    <div id="student-section" class="section" style="display: none;">
        <h1>Student Attendance</h1>
        <form method="POST">
            <input type="text" name="code" placeholder="Attendance Code" required>
            <button type="submit" name="check_code">Submit Code</button>
        </form>
        <?php 
if (isset($_SESSION['attendanceFormVisible']) && $_SESSION['attendanceFormVisible']) {
    
    echo '
    <div id="attendance-form">
        <form method="POST">
            <input type="text" name="name" placeholder="Name" required>
            <input type="text" name="surname" placeholder="Surname" required>
            <button type="submit" name="submit_attendance">Submit Attendance</button>
        </form>
    </div>';

    
    if (!empty($error1)) {
        echo '<p style="color: red;">' . $error1 . '</p>';
    }
}

?>

        <button onclick="toggleView('teacher')">Switch to Teacher View</button>
    </div>








    <script>
        function toggleView(view) {
            if (view === 'teacher') {
                document.getElementById('teacher-section').style.display = 'block';
                document.getElementById('student-section').style.display = 'none';
            } else if (view === 'student') {
                document.getElementById('teacher-section').style.display = 'none';
                document.getElementById('student-section').style.display = 'block';
            }
        }
        function toggleAttendanceList() {
            var attendanceList = document.getElementById('attendance-list');
            if (attendanceList.style.display === 'none') {
                attendanceList.style.display = 'block';
            } else {
                attendanceList.style.display = 'none';
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>