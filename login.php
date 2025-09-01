<!-- login page -->
<?php
require_once 'config/db-handler.php';

$error = '';

//CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //CSRF token validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid request. Please try again.';
    } else {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);

        //Input validation
        if (empty($username) || empty($password)) {
            $error = 'Username and password are required.';
        } else {
            //Fetch user securely
            $users = selectDataLeave('Employee', '*', 'EmployeeCode = ?', [$username]);
            if ($users && count($users) === 1) {
                $user = $users[0];
                //Use password_verify for hashed passwords
                if (password_verify($password, $user['Password'])) {
                    //Regenerate session ID to prevent fixation
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['EmployeeId'];
                    $_SESSION['username'] = $user['EmployeeCode'];
                    $_SESSION['is_approver'] = $user['IsApprover'];
                    $_SESSION['is_admin'] = $user['IsAdmin'];
                    //unset CSRF token after successful login
                    unset($_SESSION['csrf_token']);

                    if ($user['IsAdmin']) {
                        header('Location: index.php?page=dashboards/admin/dashboard');
                    } elseif ($user['IsApprover']) {
                        header('Location: index.php?page=dashboards/manager/dashboard');
                    } else {
                        header('Location: index.php?page=dashboards/employee/dashboard');
                    }
                    exit;
                } else {
                    $error = 'Invalid username or password.';
                }
            } else {
                $error = 'Invalid username or password.';
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-4">
        <div class="card shadow-sm mt-5">
            <div class="card-body">
                <h3 class="card-title text-center mb-4">Login</h3>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                <form method="post" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <div class="form-floating mb-3">
                        <input type="text" name="username" id="username" class="form-control" placeholder="Employee Code" required autofocus>
                        <label for="username">Employee Code</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="password" name="password" id="password" class="form-control" placeholder="Password" required>
                        <label for="password">Password</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </form>
            </div>
        </div>
    </div>
</div>