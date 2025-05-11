<div class="auth-container">
    <div class="auth-info">
        <h3>Join Our Print System</h3>
        <p>Create an account to start using our university print services. Upload documents, track your print jobs, and pick them up at convenient locations around campus.</p>
        
        <div class="auth-features">
            <div class="auth-feature">
                <i class="fas fa-upload"></i>
                <span>Upload documents from anywhere</span>
            </div>
            <div class="auth-feature">
                <i class="fas fa-search"></i>
                <span>Track the status of your print jobs</span>
            </div>
            <div class="auth-feature">
                <i class="fas fa-money-bill-wave"></i>
                <span>Pay with credits or cash on pickup</span>
            </div>
        </div>
        
        <div class="demo-note">
            <h4>Already registered?</h4>
            <p>If you already have an account, please <a href="index.php?page=login" class="light-link">login here</a>.</p>
        </div>
    </div>

    <div class="auth-card">
        <div class="card-header">
            <h2>Register</h2>
        </div>
        <div class="card-body">
            <?php
            // Process registration form submission
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $studentId = sanitize($_POST['student_id']);
                $name = sanitize($_POST['name']);
                $email = sanitize($_POST['email']);
                $password = $_POST['password'];
                $confirmPassword = $_POST['confirm_password'];
                
                // Validate input
                $errors = [];
                
                if (empty($studentId)) {
                    $errors[] = "Student ID is required";
                }
                
                if (empty($name)) {
                    $errors[] = "Name is required";
                }
                
                if (empty($email)) {
                    $errors[] = "Email is required";
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Invalid email format";
                }
                
                if (empty($password)) {
                    $errors[] = "Password is required";
                } elseif (strlen($password) < 6) {
                    $errors[] = "Password must be at least 6 characters";
                }
                
                if ($password !== $confirmPassword) {
                    $errors[] = "Passwords do not match";
                }
                
                // If no validation errors, attempt to register
                if (empty($errors)) {
                    $result = register($studentId, $name, $email, $password);
                    
                    if ($result === true) {
                        // Set success message
                        $_SESSION['message'] = "Registration successful! You can now login.";
                        $_SESSION['message_type'] = "success";
                        
                        // Redirect to login page
                        header("Location: index.php?page=login");
                        exit;
                    } else {
                        $errors[] = $result; // Result contains error message
                    }
                }
                
                // Display errors if any
                if (!empty($errors)) {
                    echo '<div class="alert alert-error">';
                    foreach ($errors as $error) {
                        echo $error . "<br>";
                    }
                    echo '</div>';
                }
            }
            ?>
            <form method="post" action="" class="auth-form">
                <div class="form-group">
                    <label for="student_id">Student ID</label>
                    <input type="text" id="student_id" name="student_id" value="<?php echo isset($_POST['student_id']) ? htmlspecialchars($_POST['student_id']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    <small>Please use your university email</small>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" minlength="6" required>
                    <small>Minimum 6 characters</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" minlength="6" required>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block">Register</button>
                </div>
            </form>
        </div>
        <div class="card-footer text-center">
            <p>Already have an account? <a href="index.php?page=login">Login</a></p>
        </div>
    </div>
</div>
<style>
.auth-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--spacing-8);
    max-width: 1000px;
    margin: 0 auto;
}

.auth-card {
    background-color: #d1fae5; /* Light green background */
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    overflow: hidden;
    color: #065f46; /* Dark green text */
}

.auth-info {
    padding: var(--spacing-8);
    background-color: #d1fae5; /* Light green background */
    color: #065f46;
    border-radius: var(--border-radius);
    position: relative;
    overflow: hidden;
}

.auth-info:before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: url('https://images.pexels.com/photos/256502/pexels-photo-256502.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1');
    background-size: cover;
    background-position: center;
    opacity: 0.08;
    z-index: 0;
}

.auth-info > * {
    position: relative;
    z-index: 1;
}

.auth-info h3 {
    font-size: 1.75rem;
    margin-bottom: var(--spacing-4);
}

.auth-features {
    margin-top: var(--spacing-8);
}

.auth-feature {
    display: flex;
    align-items: center;
    margin-bottom: var(--spacing-4);
}

.auth-feature i {
    margin-right: var(--spacing-3);
    font-size: 1.25rem;
    width: 24px;
    text-align: center;
    color: #10b981; /* Accent green */
}

.demo-note {
    margin-top: var(--spacing-8);
    padding: var(--spacing-4);
    background-color: #bbf7d0; /* Softer green note box */
    border-radius: var(--border-radius);
    color: #065f46;
}

.demo-note h4 {
    margin-bottom: var(--spacing-2);
}

.light-link {
    color: #065f46;
    text-decoration: underline;
}

.light-link:hover {
    color: #047857; /* Slightly darker green */
}

.auth-form {
    max-width: 400px;
    margin: 0 auto;
}

.card-footer {
    padding: var(--spacing-4);
    background-color: #bbf7d0;
    text-align: center;
    color: #065f46;
}

.text-center {
    text-align: center;
}

small {
    font-size: 0.875rem;
    color: #065f46;
    display: block;
    margin-top: var(--spacing-1);
}

@media (max-width: 768px) {
    .auth-container {
        grid-template-columns: 1fr;
    }

    .auth-info {
        display: none;
    }
}
</style>
