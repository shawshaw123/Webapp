<div class="auth-container">
    <div class="auth-card">
        <div class="card-header">
            <h2>Login</h2>
        </div>
        <div class="card-body">
            <?php
            // Process login form submission
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $studentId = sanitize($_POST['student_id']);
                $password = $_POST['password'];
                
                // Validate input
                $errors = [];
                
                if (empty($studentId)) {
                    $errors[] = "Student ID is required";
                }
                
                if (empty($password)) {
                    $errors[] = "Password is required";
                }
                
                // If no validation errors, attempt to login
                if (empty($errors)) {
                    if (login($studentId, $password)) {
                        // Redirect based on role
                        if (isAdmin()) {
                            header("Location: index.php?page=admin");
                        } else {
                            header("Location: index.php?page=dashboard");
                        }
                        exit;
                    } else {
                        $errors[] = "Invalid student ID or password";
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
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block">Login</button>
                </div>
            </form>
        </div>
        <div class="card-footer text-center">
            <p>Don't have an account? <a href="index.php?page=register">Register</a></p>
        </div>
    </div>
    
    <div class="auth-info">
        <h3>Welcome Back!</h3>
        <p>Log in to access your print dashboard, submit new print jobs, and check the status of your existing orders.</p>
        
        <div class="auth-features">
            <div class="auth-feature">
                <i class="fas fa-history"></i>
                <span>View your print history</span>
            </div>
            <div class="auth-feature">
                <i class="fas fa-file-upload"></i>
                <span>Submit new print jobs</span>
            </div>
            <div class="auth-feature">
                <i class="fas fa-credit-card"></i>
                <span>Manage your account credits</span>
            </div>
        </div>
        
        <div class="demo-credentials">
            <h4>Demo Credentials</h4>
            <p><strong>Student ID:</strong> ADMIN001</p>
            <p><strong>Password:</strong> admin123</p>
        </div>
    </div>
</div>

<style>
<style>
.auth-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--spacing-8);
    max-width: 1000px;
    margin: 0 auto;
}

.auth-card {
    background-color: #d1fae5; /* Light green */
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    overflow: hidden;
    color: #065f46; /* Dark green text */
}

.auth-info {
    padding: var(--spacing-8);
    background-color: #d1fae5; /* Light green */
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
    background: url('https://images.pexels.com/photos/3867220/pexels-photo-3867220.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1');
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

.demo-credentials {
    margin-top: var(--spacing-8);
    padding: var(--spacing-4);
    background-color: #bbf7d0; /* Lighter green */
    border: 1px solid #10b981;
    border-radius: var(--border-radius);
    color: #065f46;
}

.demo-credentials h4 {
    margin-bottom: var(--spacing-2);
}

.demo-credentials p {
    margin-bottom: var(--spacing-1);
}

.auth-form {
    max-width: 400px;
    margin: 0 auto;
}

/* New styles for card sections */
.card-header {
    padding: var(--spacing-4);
    background-color: #10b981; /* Primary green */
    color: white;
    font-weight: bold;
}

.card-body {
    padding: var(--spacing-6);
    color: #065f46;
}

.card-footer {
    padding: var(--spacing-4);
    background-color: #10b981;
    text-align: center;
    color: white;
}

.card-footer a {
    color: #ffffff;
    text-decoration: underline;
}

.text-center {
    text-align: center;
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
