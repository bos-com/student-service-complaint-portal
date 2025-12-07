<?php
session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON header
header('Content-Type: application/json');

// Check database connection
$configFile = __DIR__ . '/../config/database.php';
if (!file_exists($configFile)) {
    echo json_encode(['success' => false, 'message' => 'Database configuration not found']);
    exit;
}

require_once $configFile;

// Get POST data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $email = $_POST['email'] ?? '';
    $answer = $_POST['answer'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    
    try {
        if (empty($action)) {
            throw new Exception('Action is required');
        }
        
        if ($action === 'verify_email') {
            if (empty($email)) {
                throw new Exception('Email is required');
            }
            
            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Please enter a valid email address');
            }
            
            // Check if email exists
            $stmt = $pdo->prepare("SELECT id, email FROM students WHERE email = ?");
            $stmt->execute([$email]);
            $student = $stmt->fetch();
            
            if (!$student) {
                throw new Exception('Email not found in our system');
            }
            
            // Get first security question
            $stmt = $pdo->prepare("SELECT question FROM security_questions WHERE student_id = ? ORDER BY question_order LIMIT 1");
            $stmt->execute([$student['id']]);
            $question = $stmt->fetch();
            
            if (!$question) {
                throw new Exception('No security questions found. Please contact support.');
            }
            
            // Store in session - NO TOKEN NEEDED
            $_SESSION['reset_student_id'] = $student['id'];
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_step'] = 1;
            $_SESSION['reset_questions_answered'] = 0;
            
            echo json_encode([
                'success' => true,
                'security_question' => $question['question']
            ]);
            
        } elseif ($action === 'verify_answer') {
            if (empty($email) || empty($answer)) {
                throw new Exception('Email and answer are required');
            }
            
            // Get student from database using email
            $stmt = $pdo->prepare("SELECT id FROM students WHERE email = ?");
            $stmt->execute([$email]);
            $student = $stmt->fetch();
            $studentId = $student['id'] ?? null;
            
            if (!$studentId) {
                throw new Exception('Student not found');
            }
            
            // Get current question number from session
            $currentQuestion = $_SESSION['reset_step'] ?? 1;
            
            // Get the stored answer hash
            $stmt = $pdo->prepare("
                SELECT answer_hash, question 
                FROM security_questions 
                WHERE student_id = ? AND question_order = ?
            ");
            $stmt->execute([$studentId, $currentQuestion]);
            $securityData = $stmt->fetch();
            
            if (!$securityData) {
                throw new Exception('Security question not found');
            }
            
            // Verify answer (case insensitive)
            if (password_verify(strtolower(trim($answer)), $securityData['answer_hash'])) {
                
                // Increment questions answered count
                $_SESSION['reset_questions_answered'] = ($_SESSION['reset_questions_answered'] ?? 0) + 1;
                $questionsAnswered = $_SESSION['reset_questions_answered'];
                
                // Check if there are more questions (assuming 5 total questions)
                $nextQuestion = $currentQuestion + 1;
                $stmt = $pdo->prepare("
                    SELECT question FROM security_questions 
                    WHERE student_id = ? AND question_order = ?
                ");
                $stmt->execute([$studentId, $nextQuestion]);
                $nextQuestionData = $stmt->fetch();
                
                if ($nextQuestionData && $questionsAnswered < 5) {
                    // More questions to answer
                    $_SESSION['reset_step'] = $nextQuestion;
                    
                    echo json_encode([
                        'success' => true,
                        'has_more_questions' => true,
                        'security_question' => $nextQuestionData['question'],
                        'question_number' => $nextQuestion,
                        'questions_answered' => $questionsAnswered,
                        'message' => 'Correct! Next question...'
                    ]);
                } else {
                    // All questions answered correctly - NO TOKEN, just mark as verified
                    $_SESSION['reset_verified'] = true;
                    $_SESSION['reset_student_id'] = $studentId;
                    $_SESSION['reset_email'] = $email;
                    
                    echo json_encode([
                        'success' => true,
                        'has_more_questions' => false,
                        'message' => 'All security questions verified! You can now reset your password.',
                        'questions_answered' => $questionsAnswered
                    ]);
                }
            } else {
                throw new Exception('Incorrect answer. Please try again.');
            }
            
        } elseif ($action === 'reset_password') {
            if (empty($email) || empty($new_password)) {
                throw new Exception('Email and password are required');
            }
            
            if (strlen($new_password) < 6) {
                throw new Exception('Password must be at least 6 characters long');
            }
            
            // Check if user verified their security questions
            if (!isset($_SESSION['reset_verified']) || $_SESSION['reset_verified'] !== true) {
                throw new Exception('Please answer all security questions first.');
            }
            
            // Email must match session
            $sessionEmail = $_SESSION['reset_email'] ?? '';
            if ($sessionEmail !== $email) {
                throw new Exception('Email mismatch. Please start again.');
            }
            
            // Get student ID from email
            $stmt = $pdo->prepare("SELECT id FROM students WHERE email = ?");
            $stmt->execute([$email]);
            $student = $stmt->fetch();
            $studentId = $student['id'] ?? null;
            
            if (!$studentId) {
                throw new Exception('Student not found');
            }
            
            // Hash new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password
            $stmt = $pdo->prepare("UPDATE students SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $studentId]);
            
            if ($stmt->rowCount() > 0) {
                // Clear all reset session variables
                unset($_SESSION['reset_student_id']);
                unset($_SESSION['reset_email']);
                unset($_SESSION['reset_step']);
                unset($_SESSION['reset_verified']);
                unset($_SESSION['reset_questions_answered']);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Password reset successfully! You can now login with your new password.'
                ]);
            } else {
                throw new Exception('Failed to update password in database');
            }
        } else {
            throw new Exception('Invalid action: ' . $action);
        }
        
    } catch (PDOException $e) {
        error_log('Database error in forgot_password.php: ' . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'message' => 'Database error. Please try again later.'
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>