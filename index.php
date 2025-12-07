<?php
// Improve session handling to prevent corruption
error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', 0);

// Set session configuration before starting
ini_set('session.gc_maxlifetime', 3600);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS

// Start session with error handling
try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
} catch (Exception $e) {
    // Session corrupted, clear it
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    session_start();
}

// Check if user is already logged in
if (isset($_SESSION['student']) && !empty($_SESSION['student']['id'])) {
    header('Location: feed.php');
    exit;
}

// Define APK file path
$apk_file = 'assets/base.apk';
$apk_exists = file_exists($apk_file);
$apk_size = $apk_exists ? round(filesize($apk_file) / (1024 * 1024), 2) : 0;
$apk_filename = 'CampusVoice.apk';
$apk_version = '1.0.0';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>CampusVoice – Bugema University Student Portal</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #1E3A8A;
      --primary-light: #3B82F6;
      --secondary: #10b981;
      --bg: #ffffff;
      --bg-secondary: #f8fafc;
      --text: #0f172a;
      --text-light: #64748b;
      --border: #e2e8f0;
      --shadow: 0 2px 12px rgba(30, 58, 138, 0.08);
      --shadow-lg: 0 20px 60px rgba(30, 58, 138, 0.2);
    }

    body.dark-mode {
      --bg: #0b1220;
      --bg-secondary: #0f1724;
      --text: #e6eef8;
      --text-light: #9fb3d6;
      --border: #1f2937;
      --shadow: 0 2px 12px rgba(0, 0, 0, 0.3);
      --primary: #0ea5e9;
      --primary-light: #38bdf8;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
      overflow-x: hidden;
      transition: background 0.3s, color 0.3s;
    }

    /* ========== BLUE CURVED HEADER ========== */
    .hero-header {
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
      color: white;
      padding: 30px 15px 80px;
      position: relative;
      border-radius: 0 0 40px 40px;
      box-shadow: var(--shadow-lg);
      margin-bottom: 40px;
    }

    .hero-header::after {
      content: '';
      position: absolute;
      bottom: -1px;
      left: 0;
      right: 0;
      height: 40px;
      background: var(--bg);
      border-radius: 50% 50% 0 0 / 100% 100% 0 0;
    }

    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 15px;
    }

    .header-top {
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: relative;
      z-index: 10;
      margin-bottom: 40px;
      flex-wrap: wrap;
      gap: 15px;
    }

    .social-btn {
      position: relative;
      overflow: hidden;
    }

    .social-btn:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }

    /* Google button specific styles */
    .social-btn.google {
      background: #ffffff;
      color: #757575;
      border: 2px solid #dadce0;
    }

    .social-btn.google:hover:not(:disabled) {
      background: #f8f9fa;
      box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }

    /* Apple button specific styles */
    .social-btn.apple {
      background: #000000;
      color: #ffffff;
      border: 2px solid #000000;
    }

    .social-btn.apple:hover:not(:disabled) {
      background: #333333;
      border-color: #333333;
    }

    /* Phone button specific styles */
    .social-btn.phone {
      background: var(--primary);
      color: white;
      border: 2px solid var(--primary);
    }

    .social-btn.phone:hover:not(:disabled) {
      background: var(--primary-light);
      border-color: var(--primary-light);
    }

    .logo-section {
      display: flex;
      align-items: center;
      gap: 12px;
      flex: 1;
      min-width: 250px;
    }

    .logo-img {
      width: 60px;
      height: 60px;
      background: white;
      border-radius: 12px;
      padding: 6px;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
      object-fit: contain;
      flex-shrink: 0;
    }

    .brand-text h1 {
      font-size: 24px;
      font-weight: 900;
      margin: 0;
      color: white;
      letter-spacing: -0.5px;
      line-height: 1.2;
    }

    .brand-text p {
      font-size: 12px;
      opacity: 0.95;
      margin: 2px 0 0;
      font-weight: 500;
    }

    .header-controls {
      display: flex;
      gap: 10px;
      align-items: center;
      flex-shrink: 0;
      flex-wrap: wrap;
    }

    .icon-btn {
      width: 40px;
      height: 40px;
      background: rgba(255, 255, 255, 0.2);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.3);
      border-radius: 10px;
      color: white;
      font-size: 18px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.3s;
    }

    .icon-btn:hover {
      background: rgba(255, 255, 255, 0.3);
      transform: translateY(-2px);
      border-color: rgba(255, 255, 255, 0.5);
    }

    .hero-content {
      position: relative;
      z-index: 10;
      text-align: center;
      max-width: 800px;
      margin: 0 auto;
    }

    .hero-content h2 {
      font-size: 32px;
      font-weight: 900;
      margin-bottom: 12px;
      line-height: 1.2;
      letter-spacing: -0.5px;
    }

    .hero-content p {
      font-size: 15px;
      opacity: 0.95;
      margin-bottom: 24px;
      line-height: 1.5;
      font-weight: 500;
    }

    .cta-buttons {
      display: flex;
      gap: 12px;
      justify-content: center;
      flex-wrap: wrap;
      margin-bottom: 20px;
    }

    .btn-primary {
      padding: 14px 28px;
      background: white;
      color: var(--primary);
      border: none;
      border-radius: 10px;
      font-weight: 700;
      font-size: 14px;
      cursor: pointer;
      transition: all 0.3s;
      box-shadow: 0 6px 18px rgba(0, 0, 0, 0.12);
      display: flex;
      align-items: center;
      gap: 6px;
      flex: 1;
      min-width: 160px;
      max-width: 200px;
      justify-content: center;
    }

    .btn-primary:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    }

    .btn-secondary {
      padding: 14px 28px;
      background: rgba(255, 255, 255, 0.15);
      color: white;
      border: 2px solid white;
      border-radius: 10px;
      font-weight: 700;
      font-size: 14px;
      cursor: pointer;
      transition: all 0.3s;
      backdrop-filter: blur(10px);
      display: flex;
      align-items: center;
      gap: 6px;
      flex: 1;
      min-width: 160px;
      max-width: 200px;
      justify-content: center;
    }

    .btn-secondary:hover {
      background: white;
      color: var(--primary);
      transform: translateY(-3px);
    }

    .hero-info {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      font-size: 14px;
      opacity: 0.95;
      flex-wrap: wrap;
    }

    /* ========== MAIN CONTENT ========== */
    .main-content {
      padding: 40px 15px;
    }

    .section {
      margin-bottom: 60px;
    }

    .section-title {
      text-align: center;
      font-size: 28px;
      font-weight: 900;
      margin-bottom: 30px;
      color: var(--text);
      letter-spacing: -0.5px;
      line-height: 1.2;
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-bottom: 40px;
    }

    .stat-card {
      background: var(--bg-secondary);
      border: 2px solid var(--border);
      border-radius: 14px;
      padding: 24px;
      text-align: center;
      transition: all 0.3s;
    }

    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: var(--shadow-lg);
      border-color: var(--primary-light);
      background: var(--bg);
    }

    .stat-number {
      font-size: 36px;
      font-weight: 900;
      color: var(--primary);
      margin-bottom: 8px;
      line-height: 1;
    }

    .stat-label {
      font-size: 14px;
      color: var(--text-light);
      font-weight: 600;
    }

    .features-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 20px;
    }

    .feature-card {
      background: var(--bg-secondary);
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 28px;
      transition: all 0.3s;
    }

    .feature-card:hover {
      transform: translateY(-8px);
      box-shadow: var(--shadow-lg);
      border-color: var(--primary-light);
    }

    .feature-icon {
      font-size: 40px;
      margin-bottom: 12px;
      display: block;
    }

    .feature-card h3 {
      font-size: 18px;
      font-weight: 700;
      margin-bottom: 10px;
      color: var(--text);
      line-height: 1.3;
    }

    .feature-card p {
      font-size: 14px;
      color: var(--text-light);
      line-height: 1.6;
    }

    /* ========== MODAL STYLES ========== */
    .modal {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(30, 58, 138, 0.5);
      z-index: 1000;
      align-items: center;
      justify-content: center;
      backdrop-filter: blur(4px);
      padding: 15px;
    }

    .modal.active {
      display: flex;
    }

    .modal-content {
      background: var(--bg);
      border-radius: 18px;
      width: 100%;
      max-width: 420px;
      max-height: 90vh;
      overflow-y: auto;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
      animation: slideUp 0.4s ease;
      position: relative;
    }

    @keyframes slideUp {
      from { opacity: 0; transform: translateY(50px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .modal-close {
      position: absolute;
      top: 15px;
      right: 15px;
      background: none;
      border: none;
      color: var(--text-light);
      font-size: 28px;
      cursor: pointer;
      transition: color 0.2s;
      z-index: 10;
      width: 36px;
      height: 36px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .modal-close:hover {
      color: var(--text);
    }

    .modal-header {
      background: linear-gradient(135deg, var(--primary), var(--primary-light));
      padding: 40px 25px;
      text-align: center;
      color: white;
    }

    .modal-logo-img {
      width: 80px;
      height: 80px;
      background: white;
      border-radius: 16px;
      padding: 6px;
      margin: 0 auto 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
      object-fit: contain;
    }

    .modal-title {
      font-size: 28px;
      font-weight: 900;
      margin-bottom: 4px;
    }

    .modal-subtitle {
      font-size: 13px;
      opacity: 0.95;
    }

    .modal-body {
      padding: 30px 25px;
    }

    .modal-page { display: none; }
    .modal-page.active { display: block; }

    .form-group {
      margin-bottom: 16px;
    }

    .form-label {
      display: block;
      font-size: 12px;
      font-weight: 700;
      color: var(--primary);
      margin-bottom: 6px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    input, select, textarea {
      width: 100%;
      padding: 12px;
      background: var(--bg-secondary);
      border: 2px solid var(--border);
      border-radius: 8px;
      color: var(--text);
      font-size: 14px;
      transition: all 0.3s;
      font-family: inherit;
    }

    input:focus, select:focus, textarea:focus {
      outline: none;
      border-color: var(--primary);
      background: var(--bg);
      box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
    }

    .password-wrapper {
      position: relative;
    }

    .password-wrapper input {
      padding-right: 40px;
    }

    .password-toggle {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: var(--text-light);
      cursor: pointer;
      font-size: 16px;
      transition: color 0.2s;
    }

    .password-toggle:hover {
      color: var(--primary);
    }

    .phone-wrapper {
      display: grid;
      grid-template-columns: 120px 1fr;
      gap: 8px;
    }

    .submit-btn {
      width: 100%;
      padding: 13px;
      background: linear-gradient(135deg, var(--primary), var(--primary-light));
      color: white;
      border: none;
      border-radius: 8px;
      font-weight: 700;
      font-size: 14px;
      cursor: pointer;
      transition: all 0.3s;
      box-shadow: 0 4px 12px rgba(30, 58, 138, 0.2);
      margin-top: 12px;
    }

    .submit-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(30, 58, 138, 0.3);
    }

    .submit-btn:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }

    .error-msg, .success-msg {
      padding: 12px;
      border-radius: 8px;
      font-size: 12px;
      margin-bottom: 12px;
    }

    .error-msg {
      background: #fee2e2;
      color: #991b1b;
      border-left: 4px solid #dc2626;
    }

    .success-msg {
      background: #dcfce7;
      color: #166534;
      border-left: 4px solid #10b981;
    }

    .divider {
      display: flex;
      align-items: center;
      gap: 10px;
      margin: 20px 0;
      color: var(--text-light);
      font-size: 12px;
    }

    .divider::before, .divider::after {
      content: "";
      flex: 1;
      height: 1px;
      background: var(--border);
    }

    .social-options {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 10px;
      margin-top: 12px;
    }

    .social-btn {
      padding: 12px;
      background: var(--bg-secondary);
      border: 2px solid var(--border);
      border-radius: 8px;
      color: var(--text);
      cursor: pointer;
      font-size: 22px;
      transition: all 0.3s;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .social-btn:hover:not(:disabled) {
      background: var(--primary-light);
      border-color: var(--primary);
      color: white;
      transform: translateY(-2px);
    }

    .auth-link {
      text-align: center;
      font-size: 12px;
      color: var(--text-light);
      margin-top: 12px;
    }

    .auth-link a {
      color: var(--primary);
      font-weight: 700;
      cursor: pointer;
      text-decoration: none;
    }

    .auth-link a:hover {
      text-decoration: underline;
    }

    /* ========== CHATBOT STYLES ========== */
    .chatbot-widget {
      position: fixed;
      bottom: 20px;
      right: 20px;
      z-index: 999;
    }

    .chatbot-bubble {
      width: 56px;
      height: 56px;
      background: linear-gradient(135deg, var(--primary), var(--primary-light));
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 24px;
      cursor: pointer;
      box-shadow: 0 4px 15px rgba(30, 58, 138, 0.3);
      transition: all 0.3s;
      border: 2px solid var(--bg);
    }

    .chatbot-bubble:hover {
      transform: scale(1.08);
      box-shadow: 0 6px 20px rgba(30, 58, 138, 0.4);
    }

    .chatbot-bubble.active {
      opacity: 0;
      pointer-events: none;
    }

    .chatbot-window {
      position: absolute;
      bottom: 70px;
      right: 0;
      width: 350px;
      height: 500px;
      background: var(--bg);
      border-radius: 16px;
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
      display: flex;
      flex-direction: column;
      opacity: 0;
      pointer-events: none;
      transform: translateY(20px);
      transition: all 0.3s;
    }

    .chatbot-window.active {
      opacity: 1;
      pointer-events: auto;
      transform: translateY(0);
    }

    .chatbot-header {
      background: linear-gradient(135deg, var(--primary), var(--primary-light));
      color: white;
      padding: 16px;
      border-radius: 16px 16px 0 0;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .chatbot-title {
      font-size: 15px;
      font-weight: 700;
    }

    .chatbot-close {
      background: none;
      border: none;
      color: white;
      font-size: 20px;
      cursor: pointer;
    }

    .chatbot-messages {
      flex: 1;
      overflow-y: auto;
      padding: 15px;
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    .message {
      display: flex;
      animation: messageSlide 0.3s ease;
    }

    @keyframes messageSlide {
      from { opacity: 0; transform: translateY(8px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .message.bot {
      justify-content: flex-start;
    }

    .message.user {
      justify-content: flex-end;
    }

    .message-bubble {
      padding: 10px 14px;
      border-radius: 14px;
      max-width: 85%;
      word-wrap: break-word;
      font-size: 12px;
      line-height: 1.4;
    }

    .message.bot .message-bubble {
      background: var(--bg-secondary);
      color: var(--text);
      border-left: 3px solid var(--primary-light);
    }

    .message.user .message-bubble {
      background: var(--primary);
      color: white;
    }

    .chatbot-input {
      padding: 12px;
      border-top: 1px solid var(--border);
      display: flex;
      gap: 8px;
    }

    .chatbot-input input {
      flex: 1;
      padding: 10px 12px;
      background: var(--bg-secondary);
      border: 2px solid var(--border);
      border-radius: 20px;
      font-size: 12px;
      color: var(--text);
      transition: all 0.2s;
    }

    .chatbot-input input:focus {
      outline: none;
      border-color: var(--primary);
    }

    .chatbot-input button {
      width: 36px;
      height: 36px;
      background: var(--primary);
      border: none;
      border-radius: 50%;
      color: white;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.3s;
      flex-shrink: 0;
    }

    .chatbot-input button:hover {
      background: var(--primary-light);
      transform: scale(1.05);
    }

    /* ========== LANGUAGE SELECTOR ========== */
    .language-selector {
      position: relative;
    }

    .lang-btn {
      background: rgba(255, 255, 255, 0.2);
      color: white;
      border: 1px solid rgba(255, 255, 255, 0.3);
      padding: 8px 12px;
      border-radius: 8px;
      cursor: pointer;
      font-size: 13px;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 4px;
      transition: all 0.3s;
      backdrop-filter: blur(10px);
    }

    .lang-btn:hover {
      background: rgba(255, 255, 255, 0.3);
    }

    .lang-dropdown {
      position: absolute;
      top: 110%;
      right: 0;
      background: var(--bg);
      border: 1px solid var(--border);
      border-radius: 10px;
      margin-top: 6px;
      min-width: 140px;
      box-shadow: var(--shadow-lg);
      display: none;
      z-index: 100;
      overflow: hidden;
    }

    .lang-dropdown.active {
      display: block;
    }

    .lang-option {
      padding: 12px 16px;
      cursor: pointer;
      transition: all 0.2s;
      color: var(--text);
      font-size: 13px;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .lang-option:hover {
      background: var(--bg-secondary);
    }

    .lang-option.active {
      background: var(--primary-light);
      color: white;
      font-weight: 700;
    }

    footer {
      background: var(--bg-secondary);
      border-top: 1px solid var(--border);
      padding: 40px 15px;
      text-align: center;
      color: var(--text-light);
      font-size: 13px;
      margin-top: 60px;
    }
/* ========== APP BUTTON IN HERO ========== */
.btn-app {
    padding: 14px 28px;
    background: linear-gradient(135deg, #8b5cf6, #0ea5e9);
    color: white;
    border: none;
    border-radius: 10px;
    font-weight: 700;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 6px 18px rgba(139, 92, 246, 0.2);
    display: flex;
    align-items: center;
    gap: 8px;
    justify-content: center;
    flex: 1;
    min-width: 160px;
    max-width: 200px;
}

.btn-app:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(139, 92, 246, 0.3);
}

.btn-app:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none !important;
}

.cta-buttons {
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

/* ========== APP MODAL STYLES ========== */
.app-step {
    display: none;
}

.app-step.active {
    display: block;
    animation: slideUp 0.3s ease;
}

.instructions-tabs {
    display: flex;
    gap: 4px;
    margin-bottom: 20px;
    border-bottom: 2px solid var(--border);
    padding-bottom: 10px;
}

.instruction-tab {
    flex: 1;
    padding: 10px 12px;
    background: var(--bg-secondary);
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--text-light);
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    white-space: nowrap;
}

.instruction-tab:hover {
    background: var(--primary-light);
    color: white;
    border-color: var(--primary-light);
}

.instruction-tab.active {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}

.instruction-content {
    display: none;
}

.instruction-content.active {
    display: block;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.instruction-step {
    margin-bottom: 20px;
    padding: 16px;
    background: var(--bg-secondary);
    border-radius: 10px;
    border-left: 4px solid var(--primary);
}

.step-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 10px;
}

.step-number {
    width: 32px;
    height: 32px;
    background: var(--primary);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 16px;
    flex-shrink: 0;
}

.step-title {
    font-weight: 700;
    font-size: 16px;
    color: var(--text);
}

.step-desc {
    font-size: 14px;
    color: var(--text-light);
    line-height: 1.6;
    padding-left: 44px;
}

.step-desc ul {
    margin: 8px 0;
}

.step-desc li {
    margin-bottom: 6px;
}

.tip-card {
    background: var(--bg-secondary);
    padding: 16px;
    border-radius: 10px;
    transition: all 0.3s;
    border: 1px solid var(--border);
}

.tip-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow);
    border-color: var(--primary-light);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .btn-app {
        min-width: 140px;
        padding: 12px 20px;
        font-size: 13px;
    }
    
    .cta-buttons {
        flex-direction: row;
        align-items: center;
    }
    
    .instruction-tab {
        font-size: 12px;
        padding: 8px 10px;
    }
    
    .step-desc {
        padding-left: 0;
        margin-top: 12px;
    }
    
    .step-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
}

@media (max-width: 480px) {
    .btn-app {
        min-width: 120px;
        padding: 10px 16px;
        font-size: 12px;
    }
    
    .cta-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .instructions-tabs {
        flex-direction: column;
    }
    
    .instruction-tab {
        width: 100%;
    }
}
    /* Quick action buttons in chatbot */
    .quick-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
      margin-top: 8px;
    }

    .quick-action-btn {
      background: var(--primary-light);
      color: white;
      border: none;
      border-radius: 12px;
      padding: 5px 10px;
      font-size: 10px;
      cursor: pointer;
      transition: all 0.2s;
    }

    .quick-action-btn:hover {
      background: var(--primary);
      transform: translateY(-1px);
    }

    /* Coming Soon Styles */
    .coming-soon-badge {
      position: absolute;
      top: -6px;
      right: -6px;
      background: var(--primary);
      color: white;
      font-size: 8px;
      font-weight: 800;
      padding: 2px 5px;
      border-radius: 8px;
      transform: rotate(12deg);
      box-shadow: 0 2px 4px rgba(0,0,0,0.2);
      z-index: 2;
    }

    .social-btn.coming-soon {
      position: relative;
      opacity: 0.7;
      cursor: not-allowed;
    }

    .social-btn.coming-soon:hover {
      transform: none;
      background: var(--bg-secondary);
      border-color: var(--border);
      color: var(--text-light);
    }

    .social-btn.coming-soon.google:hover {
      background: #ffffff;
      border-color: #dadce0;
      color: #757575;
    }

    .social-btn.coming-soon.apple:hover {
      background: #000000;
      border-color: #000000;
      color: #ffffff;
    }

    .social-btn.coming-soon.phone:hover {
      background: var(--primary);
      border-color: var(--primary);
      color: white;
    }

    /* Loading spinner */
    .loading-spinner {
      display: inline-block;
      width: 16px;
      height: 16px;
      border: 2px solid #ffffff;
      border-radius: 50%;
      border-top-color: transparent;
      animation: spin 1s ease-in-out infinite;
      margin-right: 8px;
    }
    
    @keyframes spin {
      to { transform: rotate(360deg); }
    }

    /* Forgot Password Steps */
    .forgot-password-steps {
      display: none;
    }
    
    .forgot-password-steps.active {
      display: block;
    }
    
    .step-indicator {
      display: flex;
      justify-content: center;
      gap: 8px;
      margin-bottom: 20px;
    }
    
    .step {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: var(--border);
      transition: all 0.3s;
    }
    
    .step.active {
      background: var(--primary);
      transform: scale(1.2);
    }
    
    .back-btn {
      background: none;
      border: 2px solid var(--border);
      color: var(--text);
      padding: 10px 16px;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.2s;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    
    .back-btn:hover {
      border-color: var(--primary);
      color: var(--primary);
    }
    
    .btn-group {
      display: flex;
      gap: 10px;
      margin-top: 15px;
    }
    
    .btn-group .submit-btn {
      flex: 1;
      margin: 0;
    }

    /* Toast Notification Styles */
    .toast {
      position: fixed;
      top: 20px;
      right: 20px;
      padding: 12px 24px;
      border-radius: 8px;
      font-weight: 600;
      z-index: 10000;
      animation: slideIn 0.3s ease;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      color: white;
      max-width: 300px;
    }
    
    .toast.success {
      background: #10b981;
    }
    
    .toast.error {
      background: #ef4444;
    }
    
    .toast.info {
      background: var(--primary);
    }
    
    @keyframes slideIn {
      from { transform: translateX(100%); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOut {
      from { transform: translateX(0); opacity: 1; }
      to { transform: translateX(100%); opacity: 0; }
    }

    /* ========== RESPONSIVE ========== */
    @media (max-width: 768px) {
      .hero-header {
        padding: 25px 12px 60px;
        margin-bottom: 30px;
        border-radius: 0 0 30px 30px;
      }

      .header-top {
        flex-direction: column;
        text-align: center;
        gap: 12px;
        margin-bottom: 25px;
      }

      .logo-section {
        justify-content: center;
        min-width: auto;
      }

      .hero-content h2 {
        font-size: 26px;
      }

      .hero-content p {
        font-size: 14px;
      }

      .cta-buttons {
        flex-direction: column;
        align-items: center;
      }

      .btn-primary, .btn-secondary {
        width: 100%;
        max-width: 280px;
      }

      .section-title {
        font-size: 24px;
      }

      .stats-grid {
        grid-template-columns: 1fr;
        gap: 15px;
      }

      .features-grid {
        grid-template-columns: 1fr;
        gap: 15px;
      }

      .chatbot-window {
        width: calc(100vw - 40px);
        right: 10px;
        height: 450px;
        bottom: 60px;
      }

      .modal-content {
        max-width: 95%;
      }

      .phone-wrapper {
        grid-template-columns: 100px 1fr;
      }
    }

    @media (max-width: 480px) {
      .hero-header {
        padding: 20px 10px 50px;
      }

      .logo-img {
        width: 50px;
        height: 50px;
      }

      .brand-text h1 {
        font-size: 20px;
      }

      .brand-text p {
        font-size: 11px;
      }

      .hero-content h2 {
        font-size: 22px;
      }

      .cta-buttons {
        gap: 8px;
      }

      .btn-primary, .btn-secondary {
        padding: 12px 20px;
        font-size: 13px;
        min-width: 140px;
      }

      .section-title {
        font-size: 22px;
      }

      .stat-card {
        padding: 20px;
      }

      .stat-number {
        font-size: 32px;
      }

      .feature-card {
        padding: 20px;
      }

      .chatbot-window {
        width: calc(100vw - 30px);
        height: 400px;
      }

      .modal-body {
        padding: 20px 15px;
      }

      .modal-header {
        padding: 30px 20px;
      }

      .header-controls {
        justify-content: center;
        width: 100%;
      }

      .phone-wrapper {
        grid-template-columns: 1fr;
        gap: 10px;
      }
    }

    @media (max-width: 360px) {
      .hero-content h2 {
        font-size: 20px;
      }
      
      .btn-primary, .btn-secondary {
        min-width: 120px;
        padding: 10px 16px;
      }
      
      .features-grid {
        grid-template-columns: 1fr;
      }
      
      .stat-card {
        padding: 16px;
      }
    }
  </style>
</head>
<body>

 <!-- ========== BLUE CURVED HEADER ========== -->
<div class="hero-header">
    <div class="container">
        <div class="header-top">
            <div class="logo-section">
                <img src="assets/bugemalogo.jpg" alt="Bugema Logo" class="logo-img" onerror="this.style.display='none'">
                <div class="brand-text">
                    <h1>CampusVoice</h1>
                    <p>Bugema University Complaint Portal</p>
                </div>
            </div>

            <div class="header-controls">
                <div class="language-selector">
                    <button class="lang-btn" onclick="toggleLanguageDropdown()">
                        <i class="bi bi-globe"></i>
                        <span id="langLabel">EN</span>
                    </button>
                    <div class="lang-dropdown" id="langDropdown">
                        <div class="lang-option active" onclick="setLanguage('en')">
                            <i class="bi bi-check-lg"></i> English
                        </div>
                        <div class="lang-option" onclick="setLanguage('sw')">
                            <i class="bi bi-check-lg"></i> Swahili
                        </div>
                        <div class="lang-option" onclick="setLanguage('lg')"> 
                            <i class="bi bi-check-lg"></i> Luganda
                        </div>
                        <div class="lang-option" onclick="setLanguage('fr')">
                            <i class="bi bi-check-lg"></i> Français
                        </div>
                    </div>
                </div>

                <button class="icon-btn" id="themeBtn" onclick="toggleDarkMode()" title="Toggle Theme">
                    <i class="bi bi-moon-fill" id="themeIcon"></i>
                </button>

                <button class="icon-btn" onclick="openModal('loginPage')" title="Login">
                    <i class="bi bi-box-arrow-in-right"></i>
                </button>
            </div>
        </div>

        <div class="hero-content">
            <h2 id="heroTitle">Voice Your Concerns. Get Results.</h2>
            <p id="heroDesc">Direct access to university leadership. Submit complaints, track resolutions, and see real change in real-time.</p>

            <div class="cta-buttons">
                <button class="btn-primary" onclick="openModal('registerPage')" id="btnStart">
                    <i class="bi bi-pencil-square"></i> <span id="btnStartText">Start Reporting</span>
                </button>
                <button class="btn-secondary" onclick="openModal('loginPage')" id="btnSignIn">
                    <i class="bi bi-box-arrow-in-right"></i> <span id="btnSignInText">Sign In</span>
                </button>
                
                <!-- NEW: Open in App Button -->
                <button class="btn-app" onclick="openAppModal()" id="openAppBtn">
                    <i class="bi bi-phone"></i> <span id="openAppText">Open in App</span>
                </button>
            </div>

            <div class="app-download-info" style="margin-top: 15px; font-size: 13px; color: rgba(255,255,255,0.8);">
                <i class="bi bi-info-circle"></i>
                <span id="appInfoText">Better experience on mobile • <?php echo $apk_size; ?>MB • Android only</span>
            </div>

            <div class="hero-info">
                <i class="bi bi-check-circle-fill"></i>
                <span id="heroInfo">1,240+ issues resolved • 87% satisfaction</span>
            </div>
        </div>
    </div>
</div>

  <!-- ========== MAIN CONTENT ========== -->
  <main class="main-content">
    <div class="container">
      <section class="section">
        <h2 class="section-title" id="statsTitle">Why CampusVoice Works</h2>
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-number">1,240+</div>
            <div class="stat-label" id="statLabel1">Issues Resolved</div>
          </div>
          <div class="stat-card">
            <div class="stat-number">3.2h</div>
            <div class="stat-label" id="statLabel2">Avg Response Time</div>
          </div>
          <div class="stat-card">
            <div class="stat-number">87%</div>
            <div class="stat-label" id="statLabel3">Student Satisfaction</div>
          </div>
          <div class="stat-card">
            <div class="stat-number">2,340</div>
            <div class="stat-label" id="statLabel4">Active Users</div>
          </div>
        </div>
      </section>

      <section class="section">
        <h2 class="section-title" id="featuresTitle">Key Features</h2>
        <div class="features-grid">
          <div class="feature-card">
            <i class="bi bi-lightning-charge-fill feature-icon"></i>
            <h3 id="feature1Title">Lightning Fast</h3>
            <p id="feature1Desc">Report issues in seconds. Get real-time status updates.</p>
          </div>

          <div class="feature-card">
            <i class="bi bi-shield-lock-fill feature-icon"></i>
            <h3 id="feature2Title">Private & Secure</h3>
            <p id="feature2Desc">Report anonymously or with your identity. Complete data protection.</p>
          </div>

          <div class="feature-card">
            <i class="bi bi-graph-up-arrow feature-icon"></i>
            <h3 id="feature3Title">Transparent</h3>
            <p id="feature3Desc">Track progress with detailed updates every step.</p>
          </div>

          <div class="feature-card">
            <i class="bi bi-people-fill feature-icon"></i>
            <h3 id="feature4Title">Community Voice</h3>
            <p id="feature4Desc">Upvote similar issues to show priority.</p>
          </div>

          <div class="feature-card">
            <i class="bi bi-phone-fill feature-icon"></i>
            <h3 id="feature5Title">Everywhere</h3>
            <p id="feature5Desc">Access on mobile, tablet, or desktop anytime.</p>
          </div>

          <div class="feature-card">
            <i class="bi bi-check-circle-fill feature-icon"></i>
            <h3 id="feature6Title">Proven Results</h3>
            <p id="feature6Desc">87% of complaints resolved. Real change happens.</p>
          </div>
        </div>
      </section>
    </div>
  </main>

  <footer>
    <p id="footerText">© 2025 CampusVoice • Bugema University. All rights reserved.</p>
  </footer>

  <!-- ========== AUTH MODAL ========== -->
  <div class="modal" id="authModal">
    <div class="modal-content">
      <button class="modal-close" onclick="closeModal()">×</button>

      <!-- Login Page -->
      <div class="modal-page active" id="loginPage">
        <div class="modal-header">
          <img src="assets/bugemalogo.jpg" alt="Logo" class="modal-logo-img" onerror="this.style.display='none'">
          <div class="modal-title">CampusVoice</div>
          <div class="modal-subtitle" id="loginSubtitle">Complaint Portal</div>
        </div>
        <div class="modal-body">
          <div id="loginError"></div>
          <form onsubmit="handleLogin(event)" id="loginForm">
            <div class="form-group">
              <label class="form-label" id="emailLabel">Email</label>
              <input type="email" name="email" placeholder="your@email.com" required>
            </div>

            <div class="form-group">
              <label class="form-label" id="passwordLabel">Password</label>
              <div class="password-wrapper">
                <input type="password" name="password" placeholder="••••••••" required>
                <button type="button" class="password-toggle" onclick="togglePassword(this)">
                  <i class="bi bi-eye-fill"></i>
                </button>
              </div>
            </div>

            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:8px;">
              <label style="font-size:12px;color:var(--text-light);display:flex;align-items:center;gap:6px;">
                <input type="checkbox" name="remember" id="rememberCheckbox"> <span id="rememberText">Remember me</span>
              </label>
              <a href="#" style="font-size:12px;color:var(--primary);text-decoration:none;" id="forgotPassword" onclick="startForgotPassword()">Forgot password?</a>
            </div>

            <button type="submit" class="submit-btn" id="loginBtn" style="margin-top:12px;">
              <span id="loginBtnText">Sign In</span>
            </button>
          </form>

          <div class="divider" id="dividerText">Or continue with</div>
          <div class="social-options">
            <button type="button" class="social-btn google coming-soon" onclick="showComingSoon('Google')" disabled>
              <i class="bi bi-google"></i>
              <span class="coming-soon-badge">Soon</span>
            </button>
            <button type="button" class="social-btn apple coming-soon" onclick="showComingSoon('Apple')" disabled>
              <i class="bi bi-apple"></i>
              <span class="coming-soon-badge">Soon</span>
            </button>
            <button type="button" class="social-btn phone coming-soon" onclick="showComingSoon('Phone')" disabled>
              <i class="bi bi-telephone-fill"></i>
              <span class="coming-soon-badge">Soon</span>
            </button>
          </div>

          <div class="auth-link">
            <span id="noAccountText">Don't have an account?</span>
            <a onclick="switchPage('registerPage')"><span id="createLink">Create one</span></a>
          </div>
        </div>
      </div>

      <!-- Register Page -->
      <div class="modal-page" id="registerPage">
        <div class="modal-header">
          <img src="assets/bugemalogo.jpg" alt="Logo" class="modal-logo-img" onerror="this.style.display='none'">
          <div class="modal-title">CampusVoice</div>
          <div class="modal-subtitle" id="registerSubtitle">Create Account</div>
        </div>
        <div class="modal-body">
          <div id="registerError"></div>
          <form onsubmit="handleRegister(event)" id="registerForm">
            <div class="form-group">
              <label class="form-label" id="nameLabel">Full Name</label>
              <input type="text" name="name" placeholder="" required>
            </div>

            <div class="form-group">
              <label class="form-label" id="studentIdLabel">Student ID</label>
              <input type="text" name="student_id" placeholder="" required>
            </div>

            <div class="form-group">
              <label class="form-label" id="emailLabel2">Email</label>
              <input type="email" name="email" placeholder="your@email.com" required>
            </div>

            <div class="form-group">
              <label class="form-label" id="phoneLabel">Phone</label>
              <div class="phone-wrapper">
                <select name="country" id="countrySelect" onchange="updateCountryCode()" required>
                  <option value="">Select</option>
                  <option value="UG" data-code="+256">Uganda</option>
                  <option value="KE" data-code="+254">Kenya</option>
                  <option value="TZ" data-code="+255">Tanzania</option>
                  <option value="RW" data-code="+250">Rwanda</option>
                </select>
                <input type="tel" name="contact" placeholder="701234567" required>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label" id="passwordLabel2">Password</label>
              <div class="password-wrapper">
                <input type="password" name="password" placeholder="••••••••" minlength="6" required>
                <button type="button" class="password-toggle" onclick="togglePassword(this)">
                  <i class="bi bi-eye-fill"></i>
                </button>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label" id="confirmLabel">Confirm Password</label>
              <div class="password-wrapper">
                <input type="password" name="password2" placeholder="••••••••" minlength="6" required>
                <button type="button" class="password-toggle" onclick="togglePassword(this)">
                  <i class="bi bi-eye-fill"></i>
                </button>
              </div>
            </div>

            <div style="display:flex;gap:8px;align-items:center;margin-top:6px;">
              <input type="checkbox" id="agreeTerms" required>
              <label for="agreeTerms" style="font-size:12px;color:var(--text-light);" id="termsLabel">I agree to the <a href="#" style="color:var(--primary);text-decoration:underline;">Terms & Conditions</a></label>
            </div>

            <button type="submit" class="submit-btn" id="registerBtn">
              <span id="registerBtnText">Create Account</span>
            </button>
          </form>

          <div class="divider" id="dividerText2">Or sign up with</div>
          <div class="social-options">
            <button type="button" class="social-btn google coming-soon" onclick="showComingSoon('Google')" disabled>
              <i class="bi bi-google"></i>
              <span class="coming-soon-badge">Soon</span>
            </button>
            <button type="button" class="social-btn apple coming-soon" onclick="showComingSoon('Apple')" disabled>
              <i class="bi bi-apple"></i>
              <span class="coming-soon-badge">Soon</span>
            </button>
            <button type="button" class="social-btn phone coming-soon" onclick="showComingSoon('Phone')" disabled>
              <i class="bi bi-telephone-fill"></i>
              <span class="coming-soon-badge">Soon</span>
            </button>
          </div>

          <div class="auth-link">
            <span id="haveAccountText">Already have an account?</span>
            <a onclick="switchPage('loginPage')"><span id="signinLink">Sign In</span></a>
          </div>
        </div>
      </div>

      <!-- Forgot Password Page -->
      <div class="modal-page" id="forgotPasswordPage">
        <div class="modal-header">
          <img src="assets/bugemalogo.jpg" alt="Logo" class="modal-logo-img" onerror="this.style.display='none'">
          <div class="modal-title">Reset Password</div>
          <div class="modal-subtitle">Follow these steps to reset your password</div>
        </div>
        <div class="modal-body">
          <div class="step-indicator">
            <div class="step active" id="step1"></div>
            <div class="step" id="step2"></div>
            <div class="step" id="step3"></div>
          </div>

          <div id="forgotPasswordError"></div>

          <!-- Step 1: Enter Email -->
          <div class="forgot-password-steps active" id="step1Content">
            <div class="form-group">
              <label class="form-label">Enter your email address</label>
              <input type="email" id="forgotEmail" placeholder="your@email.com" required>
            </div>
            <button type="button" class="submit-btn" onclick="verifyEmail()" id="verifyEmailBtn">
              <span id="verifyEmailText">Verify Email</span>
            </button>
            <div class="auth-link">
              <a onclick="switchPage('loginPage')">Back to Sign In</a>
            </div>
          </div>

          <!-- Step 2: Security Question -->
          <div class="forgot-password-steps" id="step2Content">
            <div class="form-group">
              <label class="form-label">Answer your security question</label>
              <p style="font-size:14px;font-weight:600;margin-bottom:12px;color:var(--text);" id="securityQuestionText"></p>
              <input type="text" id="securityAnswer" placeholder="Your answer" required>
            </div>
            <div class="btn-group">
              <button type="button" class="back-btn" onclick="backToStep1()">
                <i class="bi bi-arrow-left"></i> Back
              </button>
              <button type="button" class="submit-btn" onclick="verifySecurityAnswer()" id="verifyAnswerBtn">
                <span id="verifyAnswerText">Verify Answer</span>
              </button>
            </div>
          </div>

          <!-- Step 3: Reset Password -->
          <div class="forgot-password-steps" id="step3Content">
            <div class="form-group">
              <label class="form-label">New Password</label>
              <div class="password-wrapper">
                <input type="password" id="newPassword" placeholder="••••••••" minlength="6" required>
                <button type="button" class="password-toggle" onclick="togglePassword(this)">
                  <i class="bi bi-eye-fill"></i>
                </button>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Confirm New Password</label>
              <div class="password-wrapper">
                <input type="password" id="confirmNewPassword" placeholder="••••••••" minlength="6" required>
                <button type="button" class="password-toggle" onclick="togglePassword(this)">
                  <i class="bi bi-eye-fill"></i>
                </button>
              </div>
            </div>
            <div class="btn-group">
              <button type="button" class="back-btn" onclick="backToStep2()">
                <i class="bi bi-arrow-left"></i> Back
              </button>
              <button type="button" class="submit-btn" onclick="resetPassword()" id="resetPasswordBtn">
                <span id="resetPasswordText">Reset Password</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ========== CHATBOT WIDGET ========== -->
  <div class="chatbot-widget">
    <div class="chatbot-bubble" id="chatbotBubble" onclick="toggleChatbot()">
      <i class="bi bi-chat-dots-fill"></i>
    </div>

    <div class="chatbot-window" id="chatbotWindow">
      <div class="chatbot-header">
        <div class="chatbot-title" id="chatbotTitle">CampusVoice Assistant</div>
        <button class="chatbot-close" onclick="toggleChatbot()">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>

      <div class="chatbot-messages" id="chatbotMessages">
        <div class="message bot">
          <div class="message-bubble" id="botGreeting">
            Hi! I'm CampusVoice Assistant. How can I help?
            <div class="quick-actions">
              <button class="quick-action-btn" onclick="quickAction('report')">How to report?</button>
              <button class="quick-action-btn" onclick="quickAction('anonymous')">Anonymous reporting?</button>
              <button class="quick-action-btn" onclick="quickAction('track')">Track complaint?</button>
              <button class="quick-action-btn" onclick="quickAction('contact')">Contact support?</button>
            </div>
          </div>
        </div>
      </div>

      <div class="chatbot-input">
        <input type="text" id="chatbotInput" placeholder="Ask me..." onkeypress="handleChatInput(event)">
        <button onclick="sendChatMessage()">
          <i class="bi bi-send-fill"></i>
        </button>
      </div>
    </div>
  </div>
<!-- ========== APP MODAL ========== -->
<div class="modal" id="appModal">
    <div class="modal-content" style="max-width: 550px;">
        <button class="modal-close" onclick="closeAppModal()">×</button>
        
        <div class="modal-header">
            <div class="modal-logo-img" style="background: white; width: 70px; height: 70px; border-radius: 16px; display: flex; align-items: center; justify-content: center;">
                <i class="bi bi-phone" style="font-size: 32px; color: var(--primary);"></i>
            </div>
            <div class="modal-title">Open in App</div>
            <div class="modal-subtitle" id="appModalSubtitle">Download & Install CampusVoice App</div>
        </div>
        
        <div class="modal-body">
            <!-- Step 1: Download -->
            <div class="app-step active" id="stepDownload">
                <div style="text-align: center; margin-bottom: 25px;">
                    <i class="bi bi-download" style="font-size: 48px; color: var(--primary); margin-bottom: 15px; display: block;"></i>
                    <h3 style="font-size: 22px; font-weight: 700; margin-bottom: 10px;" id="step1Title">Download APK File</h3>
                    <p style="color: var(--text-light); font-size: 14px; line-height: 1.6;" id="step1Desc">
                        Download the CampusVoice app APK file to your Android device. The app provides faster performance, push notifications, and offline access.
                    </p>
                </div>
                
                <div class="app-info-card" style="background: var(--bg-secondary); border-radius: 12px; padding: 20px; margin-bottom: 25px; border: 2px solid var(--border);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <div>
                            <div style="font-weight: 800; color: var(--text); font-size: 18px;">CampusVoice Mobile</div>
                            <div style="font-size: 13px; color: var(--text-light); margin-top: 4px;">Bugema University Official App</div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-weight: 800; color: var(--primary); font-size: 16px;">v<?php echo $apk_version; ?></div>
                            <div style="font-size: 12px; color: var(--text-light); margin-top: 2px;"><?php echo $apk_size; ?> MB</div>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-top: 15px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div style="width: 24px; height: 24px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-lightning" style="color: white; font-size: 12px;"></i>
                            </div>
                            <span style="font-size: 13px;" id="feature1">Faster Reports</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div style="width: 24px; height: 24px; background: var(--secondary); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-bell" style="color: white; font-size: 12px;"></i>
                            </div>
                            <span style="font-size: 13px;" id="feature2">Push Notifications</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div style="width: 24px; height: 24px; background: #f59e0b; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-camera" style="color: white; font-size: 12px;"></i>
                            </div>
                            <span style="font-size: 13px;" id="feature3">Camera Upload</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div style="width: 24px; height: 24px; background: #8b5cf6; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-wifi-off" style="color: white; font-size: 12px;"></i>
                            </div>
                            <span style="font-size: 13px;" id="feature4">Offline Mode</span>
                        </div>
                    </div>
                </div>
                
                <div class="download-section">
                    <button class="submit-btn" onclick="downloadAPK()" id="downloadBtn" style="margin-bottom: 12px; background: linear-gradient(135deg, #10b981, #0ea5e9);">
                        <i class="bi bi-download"></i> <span id="downloadBtnText">Download APK (<?php echo $apk_size; ?> MB)</span>
                    </button>
                    
                    <div style="text-align: center;">
                        <div style="font-size: 12px; color: var(--text-light); margin-bottom: 8px;" id="downloadNote">
                            The file will download to your device's Downloads folder
                        </div>
                        
                        <div class="auth-link">
                            <a onclick="showAppStep('instructions')" id="skipDownloadText">Already downloaded? View instructions →</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Step 2: Instructions -->
            <div class="app-step" id="stepInstructions">
                <div style="text-align: center; margin-bottom: 25px;">
                    <i class="bi bi-phone" style="font-size: 48px; color: var(--primary); margin-bottom: 15px; display: block;"></i>
                    <h3 style="font-size: 22px; font-weight: 700; margin-bottom: 10px;" id="step2Title">How to Install & Use</h3>
                    <p style="color: var(--text-light); font-size: 14px;" id="step2Desc">
                        Follow these steps to install and use the CampusVoice app on your Android device.
                    </p>
                </div>
                
                <div class="instructions-tabs" style="display: flex; gap: 8px; margin-bottom: 20px; border-bottom: 2px solid var(--border); padding-bottom: 10px;">
                    <button class="instruction-tab active" onclick="showInstructionTab('install')" id="tabInstall">
                        <i class="bi bi-download"></i> <span>Installation</span>
                    </button>
                    <button class="instruction-tab" onclick="showInstructionTab('open')" id="tabOpen">
                        <i class="bi bi-box-arrow-up-right"></i> <span>How to Open</span>
                    </button>
                    <button class="instruction-tab" onclick="showInstructionTab('tips')" id="tabTips">
                        <i class="bi bi-lightbulb"></i> <span>Usage Tips</span>
                    </button>
                </div>
                
                <!-- Installation Instructions -->
                <div class="instruction-content active" id="installContent">
                    <div class="instruction-step">
                        <div class="step-header">
                            <div class="step-number">1</div>
                            <div class="step-title" id="installStep1Title">Enable Unknown Sources</div>
                        </div>
                        <div class="step-desc" id="installStep1Desc">
                            <p>On your Android device:</p>
                            <ul style="margin-left: 20px; margin-top: 8px;">
                                <li>Go to <strong>Settings</strong> → <strong>Security</strong> or <strong>Privacy</strong></li>
                                <li>Find and enable <strong>"Unknown Sources"</strong> or <strong>"Install unknown apps"</strong></li>
                                <li>Select your browser/file manager and allow installation</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="instruction-step">
                        <div class="step-header">
                            <div class="step-number">2</div>
                            <div class="step-title" id="installStep2Title">Locate & Install APK</div>
                        </div>
                        <div class="step-desc" id="installStep2Desc">
                            <p>After downloading:</p>
                            <ul style="margin-left: 20px; margin-top: 8px;">
                                <li>Open <strong>File Manager</strong> or <strong>Downloads</strong> app</li>
                                <li>Find <strong>"<?php echo $apk_filename; ?>"</strong></li>
                                <li>Tap the file → Tap <strong>"Install"</strong></li>
                                <li>Wait for installation to complete</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="instruction-step">
                        <div class="step-header">
                            <div class="step-number">3</div>
                            <div class="step-title" id="installStep3Title">Open the App</div>
                        </div>
                        <div class="step-desc" id="installStep3Desc">
                            <p>Once installed:</p>
                            <ul style="margin-left: 20px; margin-top: 8px;">
                                <li>Look for the <strong>CampusVoice</strong> icon on your home screen or app drawer</li>
                                <li>Tap the icon to launch the app</li>
                                <li>Login with your existing account credentials</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- How to Open Instructions -->
                <div class="instruction-content" id="openContent">
                    <div class="instruction-step">
                        <div class="step-header">
                            <div class="step-number">1</div>
                            <div class="step-title" id="openStep1Title">After Installation</div>
                        </div>
                        <div class="step-desc" id="openStep1Desc">
                            <p>The app will appear on your device:</p>
                            <ul style="margin-left: 20px; margin-top: 8px;">
                                <li><strong>Home Screen:</strong> Check for CampusVoice icon</li>
                                <li><strong>App Drawer:</strong> Swipe up or tap app drawer button</li>
                                <li><strong>Search:</strong> Swipe down and type "CampusVoice"</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="instruction-step">
                        <div class="step-header">
                            <div class="step-number">2</div>
                            <div class="step-title" id="openStep2Title">First Time Setup</div>
                        </div>
                        <div class="step-desc" id="openStep2Desc">
                            <p>When you open the app for the first time:</p>
                            <ul style="margin-left: 20px; margin-top: 8px;">
                                <li>Allow necessary permissions (camera, storage, notifications)</li>
                                <li>Login with your website account (same email/password)</li>
                                <li>Enable notifications for updates</li>
                                <li>Set up biometric login if desired</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="instruction-step">
                        <div class="step-header">
                            <div class="step-number">3</div>
                            <div class="step-title" id="openStep3Title">Using the App</div>
                        </div>
                        <div class="step-desc" id="openStep3Desc">
                            <p>Key features in the app:</p>
                            <ul style="margin-left: 20px; margin-top: 8px;">
                                <li><strong>Dashboard:</strong> View all your complaints</li>
                                <li><strong>New Complaint:</strong> Tap + button to report</li>
                                <li><strong>Camera:</strong> Attach photos directly</li>
                                <li><strong>Notifications:</strong> Get updates instantly</li>
                                <li><strong>Profile:</strong> Update your details</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Usage Tips -->
                <div class="instruction-content" id="tipsContent">
                    <div class="tips-grid" style="display: grid; gap: 15px;">
                        <div class="tip-card">
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                <div style="width: 36px; height: 36px; background: linear-gradient(135deg, var(--primary), var(--primary-light)); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                    <i class="bi bi-bell" style="color: white; font-size: 16px;"></i>
                                </div>
                                <div style="font-weight: 700; font-size: 15px;" id="tip1Title">Enable Notifications</div>
                            </div>
                            <div style="font-size: 13px; color: var(--text-light); line-height: 1.5;" id="tip1Desc">
                                Turn on push notifications to receive instant updates when your complaint status changes or when admins respond.
                            </div>
                        </div>
                        
                        <div class="tip-card">
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                <div style="width: 36px; height: 36px; background: linear-gradient(135deg, #10b981, #0ea5e9); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                    <i class="bi bi-camera" style="color: white; font-size: 16px;"></i>
                                </div>
                                <div style="font-weight: 700; font-size: 15px;" id="tip2Title">Use Camera Feature</div>
                            </div>
                            <div style="font-size: 13px; color: var(--text-light); line-height: 1.5;" id="tip2Desc">
                                When reporting issues, use the in-app camera to take photos immediately. This adds strong evidence to your complaint.
                            </div>
                        </div>
                        
                        <div class="tip-card">
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                <div style="width: 36px; height: 36px; background: linear-gradient(135deg, #8b5cf6, #a78bfa); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                    <i class="bi bi-wifi-off" style="color: white; font-size: 16px;"></i>
                                </div>
                                <div style="font-weight: 700; font-size: 15px;" id="tip3Title">Offline Drafting</div>
                            </div>
                            <div style="font-size: 13px; color: var(--text-light); line-height: 1.5;" id="tip3Desc">
                                Draft complaints even without internet. The app will save them and automatically sync when you're back online.
                            </div>
                        </div>
                        
                        <div class="tip-card">
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                <div style="width: 36px; height: 36px; background: linear-gradient(135deg, #f59e0b, #fbbf24); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                    <i class="bi bi-star" style="color: white; font-size: 16px;"></i>
                                </div>
                                <div style="font-weight: 700; font-size: 15px;" id="tip4Title">Rate Responses</div>
                            </div>
                            <div style="font-size: 13px; color: var(--text-light); line-height: 1.5;" id="tip4Desc">
                                After a complaint is resolved, rate the response quality. This helps us improve service for everyone.
                            </div>
                        </div>
                    </div>
                    
                    <div style="background: linear-gradient(135deg, var(--primary-light), #8b5cf6); color: white; padding: 16px; border-radius: 12px; margin-top: 20px;">
                        <div style="display: flex; gap: 12px; align-items: center;">
                            <i class="bi bi-lightbulb" style="font-size: 24px;"></i>
                            <div>
                                <div style="font-weight: 800; margin-bottom: 4px;" id="proTipTitle">Pro Tip</div>
                                <div style="font-size: 13px; opacity: 0.9;" id="proTipDesc">
                                    Use the same login credentials as the website. All your data and complaints sync automatically between devices!
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="btn-group" style="margin-top: 25px;">
                    <button class="back-btn" onclick="showAppStep('download')">
                        <i class="bi bi-arrow-left"></i> <span id="backToDownloadText">Back to Download</span>
                    </button>
                    <button class="submit-btn" onclick="closeAppModal()" style="background: linear-gradient(135deg, var(--primary), var(--primary-light));">
                        <i class="bi bi-check-circle"></i> <span id="finishText">Got it, Thanks!</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
  <script>
    // translations object
    const translations = {
        en: {
            heroTitle: "Voice Your Concerns. Get Results.",
            heroDesc: "Direct access to university leadership. Submit complaints, track resolutions, and see real change in real-time.",
            btnStartText: "Start Reporting",
            btnSignInText: "Sign In",
            openAppText: "Open in App",
            appInfoText: "Better experience on mobile • <?php echo $apk_size; ?>MB • Android only",
            heroInfo: "1,240+ issues resolved • 87% satisfaction",
            statsTitle: "Why CampusVoice Works",
            statLabel1: "Issues Resolved",
            statLabel2: "Avg Response Time",
            statLabel3: "Student Satisfaction",
            statLabel4: "Active Users",
            featuresTitle: "Key Features",
            feature1Title: "Lightning Fast",
            feature1Desc: "Report issues in seconds. Get real-time status updates.",
            feature2Title: "Private & Secure",
            feature2Desc: "Report anonymously or with your identity. Complete data protection.",
            feature3Title: "Transparent",
            feature3Desc: "Track progress with detailed updates every step.",
            feature4Title: "Community Voice",
            feature4Desc: "Upvote similar issues to show priority.",
            feature5Title: "Everywhere",
            feature5Desc: "Access on mobile, tablet, or desktop anytime.",
            feature6Title: "Proven Results",
            feature6Desc: "87% of complaints resolved. Real change happens.",
            footerText: "© 2025 CampusVoice • Bugema University. All rights reserved.",
            loginSubtitle: "Complaint Portal",
            emailLabel: "Email",
            passwordLabel: "Password",
            rememberText: "Remember me",
            forgotPassword: "Forgot password?",
            loginBtn: "Sign In",
            dividerText: "Or continue with",
            noAccountText: "Don't have an account?",
            createLink: "Create one",
            registerSubtitle: "Create Account",
            nameLabel: "Full Name",
            studentIdLabel: "Student ID",
            emailLabel2: "Email",
            phoneLabel: "Phone",
            passwordLabel2: "Password",
            confirmLabel: "Confirm Password",
            termsLabel: "I agree to the Terms & Conditions",
            registerBtn: "Create Account",
            dividerText2: "Or sign up with",
            haveAccountText: "Already have an account?",
            signinLink: "Sign In",
            chatbotTitle: "CampusVoice Assistant",
            botGreeting: "Hi! I'm CampusVoice Assistant. How can I help?",
            verifyEmailText: "Verify Email",
            verifyAnswerText: "Verify Answer",
            resetPasswordText: "Reset Password",
            phoneVerificationText: "Enter verification code",
            sendCodeText: "Send Code",
            verifyCodeText: "Verify Code",
            resendCodeText: "Resend Code",
            // App Modal Translations
            appModalSubtitle: "Download & Install CampusVoice App",
            step1Title: "Download APK File",
            step1Desc: "Download the CampusVoice app APK file to your Android device. The app provides faster performance, push notifications, and offline access.",
            feature1: "Faster Reports",
            feature2: "Push Notifications",
            feature3: "Camera Upload",
            feature4: "Offline Mode",
            downloadBtnText: "Download APK (<?php echo $apk_size; ?> MB)",
            downloadNote: "The file will download to your device's Downloads folder",
            skipDownloadText: "Already downloaded? View instructions →",
            step2Title: "How to Install & Use",
            step2Desc: "Follow these steps to install and use the CampusVoice app on your Android device.",
            tabInstall: "Installation",
            tabOpen: "How to Open",
            tabTips: "Usage Tips",
            installStep1Title: "Enable Unknown Sources",
            installStep1Desc: "On your Android device: Go to Settings → Security or Privacy. Find and enable 'Unknown Sources' or 'Install unknown apps'. Select your browser/file manager and allow installation.",
            installStep2Title: "Locate & Install APK",
            installStep2Desc: "After downloading: Open File Manager or Downloads app. Find '<?php echo $apk_filename; ?>'. Tap the file → Tap 'Install'. Wait for installation to complete.",
            installStep3Title: "Open the App",
            installStep3Desc: "Once installed: Look for the CampusVoice icon on your home screen or app drawer. Tap the icon to launch the app. Login with your existing account credentials.",
            openStep1Title: "After Installation",
            openStep1Desc: "The app will appear on your device: Home Screen: Check for CampusVoice icon. App Drawer: Swipe up or tap app drawer button. Search: Swipe down and type 'CampusVoice'.",
            openStep2Title: "First Time Setup",
            openStep2Desc: "When you open the app for the first time: Allow necessary permissions (camera, storage, notifications). Login with your website account (same email/password). Enable notifications for updates. Set up biometric login if desired.",
            openStep3Title: "Using the App",
            openStep3Desc: "Key features in the app: Dashboard: View all your complaints. New Complaint: Tap + button to report. Camera: Attach photos directly. Notifications: Get updates instantly. Profile: Update your details.",
            tip1Title: "Enable Notifications",
            tip1Desc: "Turn on push notifications to receive instant updates when your complaint status changes or when admins respond.",
            tip2Title: "Use Camera Feature",
            tip2Desc: "When reporting issues, use the in-app camera to take photos immediately. This adds strong evidence to your complaint.",
            tip3Title: "Offline Drafting",
            tip3Desc: "Draft complaints even without internet. The app will save them and automatically sync when you're back online.",
            tip4Title: "Rate Responses",
            tip4Desc: "After a complaint is resolved, rate the response quality. This helps us improve service for everyone.",
            proTipTitle: "Pro Tip",
            proTipDesc: "Use the same login credentials as the website. All your data and complaints sync automatically between devices!",
            backToDownloadText: "Back to Download",
            finishText: "Got it, Thanks!"
        },
        sw: {
            heroTitle: "Toa Maoni Yako. Pata Matokeo.",
            heroDesc: "Ufikiaji wa moja kwa moja kwa uongozi wa chuo kikuu. Wasilisha malalamiko, fuatua ufumbuzi, na uone mabadiliko halisi kwa wakati halisi.",
            btnStartText: "Anza Kuripoti",
            btnSignInText: "Ingia",
            openAppText: "Fungua kwenye Programu",
            appInfoText: "Uzoefu bora kwenye simu • <?php echo $apk_size; ?>MB • Android pekee",
            heroInfo: "Masuala 1,240+ yametatuliwa • Kuridhika kwa 87%",
            statsTitle: "Kwa Nini CampusVoice Inafanya Kazi",
            statLabel1: "Masuala Yaliyotatuliwa",
            statLabel2: "Muda wa Majibu ya Wastani",
            statLabel3: "Kuridhika kwa Wanafunzi",
            statLabel4: "Watumiaji Waliohai",
            featuresTitle: "Vipengele Muhimu",
            feature1Title: "Haraka Sana",
            feature1Desc: "Ripoti masuala kwa sekunde. Pata visasisho vya hali halisi.",
            feature2Title: "Faragha na Salama",
            feature2Desc: "Ripoti kwa kujitolea au kwa utambulisho wako. Ulinzi kamili wa data.",
            feature3Title: "Uwazi",
            feature3Desc: "Fuatua maendeleo kwa visasisho vilivyoainishwa kila hatua.",
            feature4Title: "Sauti ya Jamii",
            feature4Desc: "Pandikiza masuala yanayofanana kuonyesha kipaumbele.",
            feature5Title: "Kila Mahali",
            feature5Desc: "Fikia kwenye rununu, kibao, au desktop wakati wowote.",
            feature6Title: "Matokeo Thabiti",
            feature6Desc: "87% ya malalamiko yametatuliwa. Mabadiliko halisi yanatokea.",
            footerText: "© 2025 CampusVoice • Chuo Kikuu cha Bugema. Haki zote zimehifadhiwa.",
            loginSubtitle: "Lango la Malalamiko",
            emailLabel: "Barua Pepe",
            passwordLabel: "Nenosiri",
            rememberText: "Nikumbuke",
            forgotPassword: "Umesahau nenosiri?",
            loginBtn: "Ingia",
            dividerText: "Au endelea na",
            noAccountText: "Huna akaunti?",
            createLink: "Unda moja",
            registerSubtitle: "Unda Akaunti",
            nameLabel: "Jina Kamili",
            studentIdLabel: "Kitambulisho cha Mwanafunzi",
            emailLabel2: "Barua Pepe",
            phoneLabel: "Simu",
            passwordLabel2: "Nenosiri",
            confirmLabel: "Thibitisha Nenosiri",
            termsLabel: "Nakubaliana na Sheria na Masharti",
            registerBtn: "Unda Akaunti",
            dividerText2: "Au jiandikishe na",
            haveAccountText: "Tayari una akaunti?",
            signinLink: "Ingia",
            chatbotTitle: "Msaidizi wa CampusVoice",
            botGreeting: "Hujambo! Mimi ni Msaidizi wa CampusVoice. Ninaweza kukusaidiaje?",
            verifyEmailText: "Thibitisha Barua Pepe",
            verifyAnswerText: "Thibitisha Jibu",
            resetPasswordText: "Weka Upya Nenosiri",
            phoneVerificationText: "Weka nambari ya uthibitisho",
            sendCodeText: "Tuma Nambari",
            verifyCodeText: "Thibitisha Nambari",
            resendCodeText: "Tuma Tena"
        },
        fr: {
            heroTitle: "Exprimez Vos Préoccupations. Obtenez des Résultats.",
            heroDesc: "Accès direct à la direction de l'université. Soumettez des plaintes, suivez les résolutions et voyez des changements réels en temps réel.",
            btnStartText: "Commencer à Signaler",
            btnSignInText: "Se Connecter",
            openAppText: "Ouvrir dans l'App",
            appInfoText: "Meilleure expérience sur mobile • <?php echo $apk_size; ?>MB • Android uniquement",
            heroInfo: "1 240+ problèmes résolus • 87% de satisfaction",
            statsTitle: "Pourquoi CampusVoice Fonctionne",
            statLabel1: "Problèmes Résolus",
            statLabel2: "Temps de Réponse Moyen",
            statLabel3: "Satisfaction des Étudiants",
            statLabel4: "Utilisateurs Actifs",
            featuresTitle: "Fonctionnalités Clés",
            feature1Title: "Extrêmement Rapide",
            feature1Desc: "Signalez des problèmes en quelques secondes. Obtenez des mises à jour en temps réel.",
            feature2Title: "Privé et Sécurisé",
            feature2Desc: "Signalez anonymement ou avec votre identité. Protection complète des données.",
            feature3Title: "Transparent",
            feature3Desc: "Suivez les progrès avec des mises à jour détaillées à chaque étape.",
            feature4Title: "Voix de la Communauté",
            feature4Desc: "Votez pour des problèmes similaires pour montrer la priorité.",
            feature5Title: "Partout",
            feature5Desc: "Accédez sur mobile, tablette ou ordinateur à tout moment.",
            feature6Title: "Résultats Éprouvés",
            feature6Desc: "87% des plaintes résolues. Des changements réels se produisent.",
            footerText: "© 2025 CampusVoice • Université Bugema. Tous droits réservés.",
            loginSubtitle: "Portail de Plaintes",
            emailLabel: "E-mail",
            passwordLabel: "Mot de Passe",
            rememberText: "Se Souvenir de Moi",
            forgotPassword: "Mot de Passe Oublié?",
            loginBtn: "Se Connecter",
            dividerText: "Ou continuer avec",
            noAccountText: "Vous n'avez pas de compte?",
            createLink: "En Créer Un",
            registerSubtitle: "Créer un Compte",
            nameLabel: "Nom Complet",
            studentIdLabel: "Numéro d'Étudiant",
            emailLabel2: "E-mail",
            phoneLabel: "Téléphone",
            passwordLabel2: "Mot de Passe",
            confirmLabel: "Confirmer le Mot de Passe",
            termsLabel: "J'accepte les Conditions Générales",
            registerBtn: "Créer un Compte",
            dividerText2: "Ou s'inscrire avec",
            haveAccountText: "Vous avez déjà un compte?",
            signinLink: "Se Connecter",
            chatbotTitle: "Assistant CampusVoice",
            botGreeting: "Bonjour! Je suis l'Assistant CampusVoice. Comment puis-je vous aider?",
            verifyEmailText: "Vérifier l'E-mail",
            verifyAnswerText: "Vérifier la Réponse",
            resetPasswordText: "Réinitialiser le Mot de Passe",
            phoneVerificationText: "Entrez le code de vérification",
            sendCodeText: "Envoyer le Code",
            verifyCodeText: "Vérifier le Code",
            resendCodeText: "Renvoyer le Code"
        },
        lg: {
            heroTitle: "Gamba Ebizibu Byo. Funa Ebizibu.",
            heroDesc: "Okutuuka ku bakulu b'a Yunivasite bulungi. Waayo ebikolwa, kkakasa n'okulaba enkyukakyuka mu kiseera kyammanga.",
            btnStartText: "Tandika Okubuulira",
            btnSignInText: "Yingira",
            openAppText: "Ggula mu App",
            appInfoText: "Enkozesa y'ongera ku simu • <?php echo $apk_size; ?>MB • Android kokka",
            heroInfo: "Ebizibu 1,240+ byeddembe • Okwenyumirira 87%",
            statsTitle: "Lwaki CampusVoice Ekozesa",
            statLabel1: "Ebizibu Byeddembe",
            statLabel2: "Obudde Bw'okuddamu",
            statLabel3: "Okwenyumirira Kw'abayizi",
            statLabel4: "Abakozesa Abaliwo",
            featuresTitle: "Ebisinga Okukulu",
            feature1Title: "Yangu Nnyo",
            feature1Desc: "Buulira ebizibu mu kaseera. Funa amawulire mu kiseera kyammanga.",
            feature2Title: "Ekyekisa ne Kituufu",
            feature2Desc: "Buulira nga toli ludda oba nga oli ludda. Ekyokulabirako ekituufu.",
            feature3Title: "Okulabikira",
            feature3Desc: "Goberera enkola n'amawulire amalala buli kkubo.",
            feature4Title: "Eddoboozi Ly'omuntu",
            feature4Desc: "Yongera ku bizibu ebyenfaanana okulaga okusookera.",
            feature5Title: "Wonna",
            feature5Desc: "Kozesa ku simu, tablet, oba desktop buli kiseera.",
            feature6Title: "Ebizibu Byeddembe",
            feature6Desc: "87% y'ebikolwa byeddembe. Enkyukakyuka etuufu eriwo.",
            footerText: "© 2025 CampusVoice • Yunivasite ya Bugema. Eddembe lyonna liri mu nsi.",
            loginSubtitle: "Liyago Ly'ebikolwa",
            emailLabel: "E-mail",
            passwordLabel: "Password",
            rememberText: "Njukira",
            forgotPassword: "Weweddemeko password?",
            loginBtn: "Yingira",
            dividerText: "Oba weyongereyo ne",
            noAccountText: "Tolina akaunti?",
            createLink: "Kola emu",
            registerSubtitle: "Kola Akaunti",
            nameLabel: "Erinnya Lyonna",
            studentIdLabel: "Namba Y'omuyizi",
            emailLabel2: "E-mail",
            phoneLabel: "Essimu",
            passwordLabel2: "Password",
            confirmLabel: "Kakasa Password",
            termsLabel: "Nnakiriziganya ne Mikutu n'Ebiragiro",
            registerBtn: "Kola Akaunti",
            dividerText2: "Oba weyongereyo ne",
            haveAccountText: "Olina akaunti?",
            signinLink: "Yingira",
            chatbotTitle: "Omuwandiisi wa CampusVoice",
            botGreeting: "Nkulamusizza! Nze ndi Omuwandiisi wa CampusVoice. Nsobola kukuyambako ki?",
            verifyEmailText: "Kakasa E-mail",
            verifyAnswerText: "Kakasa Eky'okuddamu",
            resetPasswordText: "Ssetawo Password",
            phoneVerificationText: "Yingira koodi y'okukakasa",
            sendCodeText: "Tuma Koodi",
            verifyCodeText: "Kakasa Koodi",
            resendCodeText: "Tuma Koodi Nedda"
        }
    };

    // Global variables
    let currentLanguage = localStorage.getItem('language') || 'en';
    let currentForgotPasswordStep = 1;
    let forgotPasswordEmail = '';
    let securityQuestion = '';
    let chatbotOpen = false;
    let currentAppStep = 'download';
    let currentInstructionTab = 'install';

    // ========== TOAST NOTIFICATION FUNCTION ==========
    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        document.body.appendChild(toast);
        
        // Add animation styles if not already added
        if (!document.getElementById('toast-animations')) {
            const style = document.createElement('style');
            style.id = 'toast-animations';
            style.textContent = `
                @keyframes slideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                @keyframes slideOut {
                    from { transform: translateX(0); opacity: 1; }
                    to { transform: translateX(100%); opacity: 0; }
                }
            `;
            document.head.appendChild(style);
        }
        
        // Remove after 3 seconds
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    // ========== COMING SOON FUNCTIONS ==========
    function showComingSoon(platform) {
        showToast(`${platform} sign-in is coming soon! We're working on it.`, 'info');
    }

    // ========== LANGUAGE FUNCTIONS ==========
    function applyLanguage(lang) {
        const strings = translations[lang] || translations.en;
        Object.keys(strings).forEach(key => {
            const el = document.getElementById(key);
            if (el) el.textContent = strings[key];
        });
    }

    function setLanguage(lang) {
        currentLanguage = lang;
        localStorage.setItem('language', lang);
        applyLanguage(lang);
        const lbl = document.getElementById('langLabel');
        if (lbl) lbl.textContent = lang.toUpperCase();
        document.querySelectorAll('.lang-option').forEach(el => el.classList.remove('active'));
        const opt = document.querySelector(`.lang-option[onclick="setLanguage('${lang}')"]`);
        if (opt) opt.classList.add('active');
        closeLanguageDropdown();
        
        // Update chatbot language if open
        updateChatbotLanguage();
        
        // Update chatbot messages if any exist
        const botGreeting = document.getElementById('botGreeting');
        if (botGreeting) {
            const responses = chatbotResponses[lang] || chatbotResponses.en;
            botGreeting.innerHTML = responses.greetingResponse + getQuickActions(lang);
        }
        
        // Update app modal if open
        updateAppModalLanguage();

        // Persist language choice to server-session
        try {
            fetch('api/set_language.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ lang })
            }).catch(() => {});
        } catch (e) {
            // non-blocking
        }
    }

    function toggleLanguageDropdown() {
        const d = document.getElementById('langDropdown');
        if (d) d.classList.toggle('active');
    }

    function closeLanguageDropdown() {
        const d = document.getElementById('langDropdown');
        if (d) d.classList.remove('active');
    }

    // ========== THEME FUNCTIONS ==========
    function toggleDarkMode() {
        document.body.classList.toggle('dark-mode');
        const isDark = document.body.classList.contains('dark-mode');
        localStorage.setItem('darkMode', isDark);
        const icon = document.getElementById('themeIcon');
        if (icon) icon.className = isDark ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
    }

    // ========== MODAL FUNCTIONS ==========
    function openModal(pageId) {
        console.log('Opening modal for page:', pageId);
        
        const auth = document.getElementById('authModal');
        if (auth) {
            auth.classList.add('active');
            console.log('Modal set to active');
            
            setTimeout(() => {
                switchPage(pageId);
            }, 10);
        } else {
            console.error('Auth modal not found!');
        }
    }

    function closeModal() {
        const auth = document.getElementById('authModal');
        if (auth) {
            auth.classList.remove('active');
            console.log('Modal closed');
        }
        
        resetForgotPassword();
    }

    function switchPage(pageId) {
        console.log('Switching to page:', pageId);
        
        document.querySelectorAll('.modal-page').forEach(p => {
            p.classList.remove('active');
        });
        
        const page = document.getElementById(pageId);
        if (page) {
            page.classList.add('active');
            console.log('Page activated:', pageId);
        } else {
            console.error('Page not found:', pageId);
        }
        
        // Clear any error messages
        ['loginError', 'registerError', 'forgotPasswordError'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerHTML = '';
        });
        
        if (pageId !== 'forgotPasswordPage') {
            resetForgotPassword();
        }
    }

    // ========== FORM UTILITY FUNCTIONS ==========
    function togglePassword(btn) {
        const input = btn.parentElement.querySelector('input');
        input.type = input.type === 'password' ? 'text' : 'password';
        btn.querySelector('i').className = input.type === 'password' ? 'bi bi-eye-fill' : 'bi bi-eye-slash-fill';
    }

    function updateCountryCode() {
        const select = document.getElementById('countrySelect');
        if (!select) return;
        const selected = select.options[select.selectedIndex];
        if (selected) {
            const code = selected.dataset.code || '';
            const contact = document.querySelector('input[name="contact"]');
            if (contact) contact.placeholder = code ? `${code.slice(1)}XXXXXXX` : '701234567';
        }
    }

    // ========== APP MODAL FUNCTIONS ==========
    function openAppModal() {
        const modal = document.getElementById('appModal');
        if (modal) {
            modal.classList.add('active');
            showAppStep('download');
            updateAppModalLanguage();
        }
    }

    function closeAppModal() {
        const modal = document.getElementById('appModal');
        if (modal) {
            modal.classList.remove('active');
        }
    }

    function showAppStep(step) {
        currentAppStep = step;
        
        // Hide all steps
        document.querySelectorAll('.app-step').forEach(s => {
            s.classList.remove('active');
        });
        
        // Show selected step
        const stepElement = document.getElementById(`step${step.charAt(0).toUpperCase() + step.slice(1)}`);
        if (stepElement) {
            stepElement.classList.add('active');
        }
    }

    function showInstructionTab(tab) {
        currentInstructionTab = tab;
        
        // Update active tab
        document.querySelectorAll('.instruction-tab').forEach(t => {
            t.classList.remove('active');
        });
        const tabElement = document.getElementById(`tab${tab.charAt(0).toUpperCase() + tab.slice(1)}`);
        if (tabElement) {
            tabElement.classList.add('active');
        }
        
        // Show corresponding content
        document.querySelectorAll('.instruction-content').forEach(c => {
            c.classList.remove('active');
        });
        const contentElement = document.getElementById(`${tab}Content`);
        if (contentElement) {
            contentElement.classList.add('active');
        }
    }

    async function downloadAPK() {
        const downloadBtn = document.getElementById('downloadBtn');
        const originalText = downloadBtn.innerHTML;
        
        try {
            downloadBtn.disabled = true;
            downloadBtn.innerHTML = '<span class="loading-spinner"></span> Preparing...';
            
            <?php if ($apk_exists): ?>
            // Create download link
            const apkUrl = 'assets/base.apk';
            const link = document.createElement('a');
            link.href = apkUrl;
            link.download = '<?php echo $apk_filename; ?>';
            link.style.display = 'none';
            document.body.appendChild(link);
            
            // Trigger download
            link.click();
            
            // Clean up
            setTimeout(() => {
                document.body.removeChild(link);
            }, 100);
            
            // Show success message
            showToast('Download started! Check your downloads folder.', 'success');
            
            // Auto-proceed to instructions after 2 seconds
            setTimeout(() => {
                showAppStep('instructions');
                showInstructionTab('install');
            }, 2000);
            
            <?php else: ?>
            showToast('APK file not found on server. Please contact support.', 'error');
            <?php endif; ?>
            
        } catch (error) {
            console.error('Download error:', error);
            showToast('Download failed. Please try again.', 'error');
        } finally {
            downloadBtn.disabled = false;
            downloadBtn.innerHTML = '<i class="bi bi-download"></i> <span id="downloadBtnText">Download APK (<?php echo $apk_size; ?> MB)</span>';
        }
    }

    function updateAppModalLanguage() {
        const lang = currentLanguage || 'en';
        const strings = translations[lang] || translations.en;
        
        // Update all app modal elements
        Object.keys(strings).forEach(key => {
            const el = document.getElementById(key);
            if (el) el.textContent = strings[key];
        });
    }

    // ========== FORGOT PASSWORD FUNCTIONS ==========
    function resetForgotPassword() {
        currentForgotPasswordStep = 1;
        forgotPasswordEmail = '';
        securityQuestion = '';
        
        // Hide all steps and show only step 1
        document.querySelectorAll('.forgot-password-steps').forEach(step => {
            step.classList.remove('active');
        });
        const step1 = document.getElementById('step1Content');
        if (step1) step1.classList.add('active');
        
        // Reset step indicators
        document.querySelectorAll('.step').forEach((step, index) => {
            step.classList.toggle('active', index === 0);
        });
        
        // Clear form inputs
        const forgotEmail = document.getElementById('forgotEmail');
        const securityAnswer = document.getElementById('securityAnswer');
        const newPassword = document.getElementById('newPassword');
        const confirmNewPassword = document.getElementById('confirmNewPassword');
        
        if (forgotEmail) forgotEmail.value = '';
        if (securityAnswer) securityAnswer.value = '';
        if (newPassword) newPassword.value = '';
        if (confirmNewPassword) confirmNewPassword.value = '';
        function updateAppModalLanguage() {
    const lang = currentLanguage || 'en';
    const strings = translations[lang] || translations.en;
    
    // Update all app modal text elements by ID
    Object.keys(strings).forEach(key => {
        const el = document.getElementById(key);
        if (el) {
            // For HTML content (descriptions with line breaks), use innerHTML
            if (key.includes('Desc') || key.includes('Text') || key.includes('Help')) {
                el.innerHTML = strings[key];
            } else {
                el.textContent = strings[key];
            }
        }
    });
    
    // Update button texts specifically
    const downloadBtn = document.getElementById('downloadBtn');
    if (downloadBtn) {
        downloadBtn.innerHTML = `<i class="bi bi-download"></i> <span id="downloadBtnText">${strings.downloadBtnText || 'Download APK'}</span>`;
    }
    
    const backToDownloadBtn = document.getElementById('backToDownloadBtn');
    if (backToDownloadBtn) {
        backToDownloadBtn.textContent = strings.backToDownloadText || 'Back to Download';
    }
    
    const finishBtn = document.getElementById('finishBtn');
    if (finishBtn) {
        finishBtn.textContent = strings.finishText || 'Got it, Thanks!';
    }
}
        // Clear error message
        const errorDiv = document.getElementById('forgotPasswordError');
        if (errorDiv) errorDiv.innerHTML = '';
    }

    function showStep(step) {
        document.querySelectorAll('.forgot-password-steps').forEach(s => {
            s.classList.remove('active');
        });
        const stepContent = document.getElementById(`step${step}Content`);
        if (stepContent) stepContent.classList.add('active');
        
        // Update step indicators
        document.querySelectorAll('.step').forEach((indicator, index) => {
            indicator.classList.toggle('active', index < step);
        });
    }

    function startForgotPassword() {
        switchPage('forgotPasswordPage');
        resetForgotPassword();
    }

    function backToStep1() {
        currentForgotPasswordStep = 1;
        showStep(1);
        document.getElementById('forgotPasswordError').innerHTML = '';
    }

    function backToStep2() {
        currentForgotPasswordStep = 2;
        showStep(2);
        document.getElementById('forgotPasswordError').innerHTML = '';
    }

    async function verifyEmail() {
        const emailInput = document.getElementById('forgotEmail');
        if (!emailInput) {
            console.error('Email input not found');
            return;
        }
        
        const email = emailInput.value.trim();
        const verifyBtn = document.getElementById('verifyEmailBtn');
        const errorDiv = document.getElementById('forgotPasswordError');
        const originalText = verifyBtn.innerHTML;
        
        try {
            // Validate email
            if (!email) {
                throw new Error('Please enter your email address');
            }
            
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                throw new Error('Please enter a valid email address');
            }
            
            verifyBtn.disabled = true;
            verifyBtn.innerHTML = '<span class="loading-spinner"></span> Verifying...';
            errorDiv.innerHTML = '';
            
            console.log('Sending verify request for:', email);
            
            // Send as FormData
            const formData = new FormData();
            formData.append('action', 'verify_email');
            formData.append('email', email);
            
            const response = await fetch('api/forgot_password.php', {
                method: 'POST',
                body: formData
            });
            
            console.log('Response status:', response.status);
            
            if (!response.ok) {
                const text = await response.text();
                console.log('Raw error response:', text);
                throw new Error(`Server error: ${response.status}`);
            }
            
            const data = await response.json();
            console.log('Response data:', data);
            
            if (data.success) {
                // Store email globally for next steps
                forgotPasswordEmail = email;
                
                // Move to step 2
                currentForgotPasswordStep = 2;
                
                // Update UI with security question
                const securityQuestionEl = document.getElementById('securityQuestionText');
                if (securityQuestionEl) {
                    securityQuestionEl.textContent = data.security_question || 'Security question not found';
                }
                
                // Show step 2
                showStep(2);
                
                // Show success message
                errorDiv.innerHTML = '<div class="success-msg">Email verified! Please answer your security question.</div>';
                
            } else {
                throw new Error(data.message || 'Email verification failed');
            }
            
        } catch (error) {
            console.error('Verify email error:', error);
            errorDiv.innerHTML = `<div class="error-msg">${error.message}</div>`;
        } finally {
            verifyBtn.disabled = false;
            verifyBtn.innerHTML = originalText;
        }
    }

    async function verifySecurityAnswer() {
        const answer = document.getElementById('securityAnswer').value.trim();
        const btn = document.getElementById('verifyAnswerBtn');
        const errorDiv = document.getElementById('forgotPasswordError');

        if (!answer) {
            errorDiv.innerHTML = '<div class="error-msg">Please enter your answer</div>';
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="loading-spinner"></span> Verifying...';

        try {
            console.log('Verifying security answer for:', forgotPasswordEmail);
            
            // Send as FormData
            const formData = new FormData();
            formData.append('action', 'verify_answer');
            formData.append('email', forgotPasswordEmail);
            formData.append('answer', answer);
            
            const response = await fetch('api/forgot_password.php', {
                method: 'POST',
                body: formData
            });

            console.log('Response status:', response.status);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const text = await response.text();
            console.log('Raw response:', text);
            
            let data;
            try {
                data = JSON.parse(text);
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                throw new Error('Invalid response from server');
            }

            if (data.success) {
                currentForgotPasswordStep = 3;
                showStep(3);
                errorDiv.innerHTML = '<div class="success-msg">Security answer verified! You can now reset your password.</div>';
                console.log('Security answer verified successfully');
            } else {
                errorDiv.innerHTML = `<div class="error-msg">${data.message || 'Incorrect answer'}</div>`;
                console.log('Security answer verification failed:', data.message);
            }
        } catch (error) {
            console.error('Verify security answer error:', error);
            errorDiv.innerHTML = '<div class="error-msg">Connection error. Please try again.</div>';
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<span id="verifyAnswerText">Verify Answer</span>';
        }
    }

    async function resetPassword() {
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmNewPassword').value;
        const btn = document.getElementById('resetPasswordBtn');
        const errorDiv = document.getElementById('forgotPasswordError');

        if (newPassword !== confirmPassword) {
            errorDiv.innerHTML = '<div class="error-msg">Passwords do not match</div>';
            return;
        }

        if (newPassword.length < 6) {
            errorDiv.innerHTML = '<div class="error-msg">Password must be at least 6 characters</div>';
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="loading-spinner"></span> Resetting...';

        try {
            console.log('Resetting password for:', forgotPasswordEmail);
            
            // Send as FormData
            const formData = new FormData();
            formData.append('action', 'reset_password');
            formData.append('email', forgotPasswordEmail);
            formData.append('new_password', newPassword);
            
            const response = await fetch('api/forgot_password.php', {
                method: 'POST',
                body: formData
            });

            console.log('Response status:', response.status);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const text = await response.text();
            console.log('Raw response:', text);
            
            let data;
            try {
                data = JSON.parse(text);
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                throw new Error('Invalid response from server');
            }

            if (data.success) {
                errorDiv.innerHTML = '<div class="success-msg">Password reset successfully! You can now sign in with your new password.</div>';
                console.log('Password reset successfully');
                
                // Redirect to login page after 2 seconds
                setTimeout(() => {
                    closeModal();
                    switchPage('loginPage');
                    resetForgotPassword();
                }, 2000);
            } else {
                errorDiv.innerHTML = `<div class="error-msg">${data.message || 'Failed to reset password'}</div>`;
                console.log('Password reset failed:', data.message);
            }
        } catch (error) {
            console.error('Reset password error:', error);
            errorDiv.innerHTML = '<div class="error-msg">Connection error. Please try again.</div>';
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<span id="resetPasswordText">Reset Password</span>';
        }
    }

    // ========== LOGIN FUNCTION ==========
    async function handleLogin(event) {
        event.preventDefault();
        const form = event.target;
        const email = form.email.value.trim();
        const password = form.password.value;

        const btn = document.getElementById('loginBtn');
        const errorDiv = document.getElementById('loginError');
        
        errorDiv.innerHTML = '';
        
        btn.disabled = true;
        btn.innerHTML = '<span class="loading-spinner"></span> Signing in...';

        try {
            const res = await fetch('api/login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ email, password })
            });
            
            if (!res.ok) {
                throw new Error('Server error');
            }
            
            const json = await res.json();

            if (json.success) {
                showToast('Login successful!', 'success');
                closeModal();
                setTimeout(() => {
                    window.location.href = json.redirect;
                }, 1000);
            } else {
                errorDiv.innerHTML = `<div class="error-msg">${json.message || 'Login failed'}</div>`;
            }
        } catch (err) {
            console.error('Login error:', err);
            errorDiv.innerHTML = '<div class="error-msg">Connection error. Please check your internet and try again.</div>';
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<span id="loginBtnText">Sign In</span>';
        }
    }

    // ========== REGISTRATION FUNCTION ==========
    async function handleRegister(event) {
        event.preventDefault();
        const form = event.target;
        const btn = document.getElementById('registerBtn');
        const errorDiv = document.getElementById('registerError');

        // Basic validation
        if (form.password.value !== form.password2.value) {
            errorDiv.innerHTML = '<div class="error-msg">Passwords do not match</div>';
            return;
        }

        if (!document.getElementById('agreeTerms').checked) {
            errorDiv.innerHTML = '<div class="error-msg">You must agree to the terms and conditions</div>';
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="loading-spinner"></span> Creating...';

        const countryCode = form.country.value;
        const contact = form.contact.value.trim();
        const codeMap = { 'UG': '+256', 'KE': '+254', 'TZ': '+255', 'RW': '+250' };

        const data = {
            name: form.name.value.trim(),
            student_id: form.student_id.value.trim(),
            email: form.email.value.trim(),
            contact: (codeMap[countryCode] || '') + contact,
            password: form.password.value.trim()
        };

        try {
            const res = await fetch('api/register.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            
            const json = await res.json();

            if (json.success && json.redirect) {
                showToast('Account created successfully!', 'success');
                
                setTimeout(() => {
                    window.location.href = json.redirect;
                }, 1500);
            } else {
                errorDiv.innerHTML = `<div class="error-msg">${json.message || 'Registration failed'}</div>`;
            }
        } catch (err) {
            console.error('Registration error:', err);
            errorDiv.innerHTML = '<div class="error-msg">Connection error. Please try again.</div>';
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<span id="registerBtnText">Create Account</span>';
        }
    }

    // ========== ENHANCED CHATBOT FUNCTIONS ==========
    const chatbotResponses = {
        en: {
            greetings: ["hi", "hello", "hey", "good morning", "good afternoon", "good evening"],
            greetingResponse: "Hello! I'm CampusVoice Assistant. How can I help you today?",
            
            defaultVerificationCode: "1948",
            
            responses: {
                "forgot password": "Click 'Forgot Password' on the login page. You'll need to verify your email and answer your security question to reset your password.",
                "verification code": "The default verification code for account setup is: <strong>1948</strong>. This is used during initial account verification.",
                "reset password": "To reset your password: 1) Go to login page 2) Click 'Forgot Password' 3) Enter your email 4) Answer security question 5) Create new password.",
                "social login": "Social login options (Google, Apple, Phone) are coming soon! Currently, please use email and password to sign in.",
                "report issue": "To report an issue: 1. Sign in 2. Click 'New Complaint' 3. Fill in details 4. Submit. You can add photos and mark priority levels.",
                "anonymous report": "Toggle 'Post Anonymously' when submitting a complaint. Your name will be hidden from other students but admins can still see it for verification.",
                "track complaint": "Check your dashboard to track complaints. Status colors: Blue=Pending, Yellow=In Progress, Green=Resolved, Gray=Closed.",
                "contact support": "Support contacts: Email support@campusvoice.bugema.ac.ug | Phone +256 777587915 | Office: Student Affairs Building, Room 101.",
                "account registration": "To register: 1) Click 'Start Reporting' 2) Fill all required fields 3) Use valid student ID 4) Set strong password 5) Agree to terms.",
                "student id format": "Student ID should follow this format: YY/department/BU/R/0001 (Example: 22/BIT/BU/R/0001 for 2022 intake, BIT department).",
                "complaint categories": "Available categories: Academic Issues, Hostel Problems, Finance Matters, Health & Safety, Infrastructure, Library Services, Cafeteria, Others.",
                "response time": "Average response time is 3.2 hours for urgent issues. Normal complaints are addressed within 24-48 hours.",
                "emergency": "For emergencies requiring immediate attention, please call: +256 777587915 or visit Student Affairs Office immediately.",
                "privacy": "Your data is protected under GDPR and Ugandan data protection laws. We never share your information without consent.",
                "feature request": "To request new features, email: features@campusvoice.bugema.ac.ug with subject 'Feature Request'.",
                "bug report": "Found a bug? Report it to: techsupport@campusvoice.bugema.ac.ug with details and screenshots if possible.",
                "working hours": "Support is available: Mon-Fri: 8AM-5PM, Sat: 9AM-1PM, Sun: Closed. Emergency contact available 24/7.",
                "data export": "You can export your complaint history from your dashboard. Go to 'My Profile' → 'Export Data'.",
                "language change": "Click the globe icon in the top right to change language. Available: English, Swahili, Luganda, French.",
                "dark mode": "Click the moon/sun icon to toggle dark/light mode for better visibility in different lighting conditions.",
                "mobile app": "Mobile app is coming soon! Currently access via mobile browser at the same URL.",
                "thank you": "You're welcome! Is there anything else I can help you with?",
                "bye": "Goodbye! Remember, your voice matters. Have a great day!"
            },
            
            quickActions: [
                { label: "Report Issue", action: "report" },
                { label: "Forgot Password", action: "forgot password" },
                { label: "Verification Code", action: "verification code" },
                { label: "Contact Support", action: "contact support" }
            ]
        },
        
        sw: {
            greetings: ["hujambo", "jambo", "mambo", "habari", "salamu"],
            greetingResponse: "Hujambo! Mimi ni Msaidizi wa CampusVoice. Ninaweza kukusaidia nini leo?",
            
            defaultVerificationCode: "1948",
            
            responses: {
                "forgot password": "Bonyeza 'Umesahau nenosiri' kwenye ukurasa wa kuingia. Utahitaji kuthibitisha barua pepe yako na kujibu swali lako la usalama ili kuweka upya nenosiri lako.",
                "verification code": "Nambari ya kawaida ya uthibitisho ya usanidi wa akaunti ni: <strong>1948</strong>. Hii inatumiwa wakati wa uthibitisho wa awali wa akaunti.",
                "reset password": "Kuwa upya nenosiri lako: 1) Nenda kwenye ukurasa wa kuingia 2) Bonyeza 'Umesahau nenosiri' 3) Weka barua pepe yako 4) Jibu swali la usalama 5) Unda nenosiri jipya.",
                "social login": "Chaguo za kuingia kwa mitandao ya kijamii (Google, Apple, Simu) zina kuja hivi karibuni! Kwa sasa, tafadhali tumia barua pepe na nenosiri kuingia.",
                "report issue": "Kuripoti tatizo: 1. Ingia 2. Bonyeza 'Malalamiko Mapya' 3. Jaza maelezo 4. Wasilisha. Unaweza kuongeza picha na kuashiria viwango vya kipaumbele.",
                "anonymous report": "Badilisha 'Tuma Kwa Kujitolea' unapotuma malalamiko. Jina lako litawekwa wazi kwa wanafunzi wengine lakini wasimamizi bado wanaweza kuliona kwa ajili ya uthibitisho.",
                "track complaint": "Angalia dashibodi yako kufuatilia malalamiko. Rangi za hali: Bluu=Inasubiri, Njano=Inaendelea, Kijani=Imetatuliwa, Kijivu=Imefungwa.",
                "contact support": "Mawasiliano ya msaada: Barua pepe support@campusvoice.bugema.ac.ug | Simu +256 777587915 | Ofisi: Jengo la Mambo ya Wanafunzi, Chumba 101.",
                "account registration": "Kujiandikisha: 1) Bonyeza 'Anza Kuripoti' 2) Jaza sehemu zote zinazohitajika 3) Tumia kitambulisho halali cha mwanafunzi 4) Weka nenosiri thabiti 5) Kubali masharti.",
                "thank you": "Karibu! Kuna jambo lingine ninaweza kukusaidia nalo?",
                "bye": "Kwaheri! Kumbuka, sauti yako ni muhimu. Uwe na siku njema!"
            }
        },
        
        fr: {
            greetings: ["bonjour", "salut", "coucou", "bonsoir"],
            greetingResponse: "Bonjour! Je suis l'Assistant CampusVoice. Comment puis-je vous aider aujourd'hui?",
            
            defaultVerificationCode: "1948",
            
            responses: {
                "forgot password": "Cliquez sur 'Mot de Passe Oublié' sur la page de connexion. Vous devrez vérifier votre e-mail et répondre à votre question de sécurité pour réinitialiser votre mot de passe.",
                "verification code": "Le code de vérification par défaut pour la configuration du compte est: <strong>1948</strong>. Ceci est utilisé lors de la vérification initiale du compte.",
                "reset password": "Pour réinitialiser votre mot de passe: 1) Allez à la page de connexion 2) Cliquez 'Mot de Passe Oublié' 3) Entrez votre e-mail 4) Répondez à la question de sécurité 5) Créez un nouveau mot de passe.",
                "thank you": "De rien! Y a-t-il autre chose que je puisse faire pour vous?",
                "bye": "Au revoir! N'oubliez pas que votre voix compte. Passez une bonne journée!"
            }
        },
        
        lg: {
            greetings: ["nkulamusizza", "wasuze otya", "oli otya", "bulungi"],
            greetingResponse: "Nkulamusizza! Nze ndi Omuwandiisi wa CampusVoice. Nsobola kukuyambako ki leero?",
            
            defaultVerificationCode: "1948",
            
            responses: {
                "forgot password": "Koona 'Weweddemeko password' ku lupapula lw'okuyingira. Ojja kusaba okukakasa e-mail yo n'okuddamu ekibuuzo ky'okwerinda okusalawo password yo.",
                "verification code": "Koodi y'okukakasa ey'okulondoola ya account: <strong>1948</strong>. Eno ekozesebwa mu kiseera ky'okukakasa account mu ntandikwa.",
                "reset password": "Okusalawo password yo: 1) Genda ku lupapula lw'okuyingira 2) Koona 'Weweddemeko password' 3) Yingira e-mail yo 4) Ddamu ekibuuzo ky'okwerinda 5) Kola password enpya.",
                "thank you": "Kaale! Waliwo ekirala nsobola kukukolera?",
                "bye": "Weeraba! Jjukira, eddoboozi lyo likulu. Nkwagaliza olunaku olulungi!"
            }
        }
    };

    function toggleChatbot() {
        chatbotOpen = !chatbotOpen;
        document.getElementById('chatbotBubble').classList.toggle('active');
        document.getElementById('chatbotWindow').classList.toggle('active');
        
        // Update chatbot title based on language
        updateChatbotLanguage();
    }

    function sendChatMessage() {
        const input = document.getElementById('chatbotInput');
        const msg = input.value.trim();
        if (!msg) return;

        addChatMessage('user', msg);
        input.value = '';

        setTimeout(() => {
            const response = generateBotResponse(msg);
            addChatMessage('bot', response);
        }, 500);
    }

    function addChatMessage(sender, text) {
        const msgDiv = document.createElement('div');
        msgDiv.className = `message ${sender}`;
        
        // Format text with line breaks
        const formattedText = text.replace(/\n/g, '<br>');
        
        // Add quick actions for bot messages
        let content = `<div class="message-bubble">${formattedText}</div>`;
        
        msgDiv.innerHTML = content;
        document.getElementById('chatbotMessages').appendChild(msgDiv);
        document.getElementById('chatbotMessages').scrollTop = document.getElementById('chatbotMessages').scrollHeight;
    }

    function generateBotResponse(msg) {
        const m = msg.toLowerCase();
        const lang = currentLanguage || 'en';
        const responses = chatbotResponses[lang] || chatbotResponses.en;
        
        // Check for greetings
        if (responses.greetings.some(greet => m.includes(greet))) {
            return responses.greetingResponse + getQuickActions(lang);
        }
        
        // Check for thank you
        if (m.includes('thank') || m.includes('thanks') || m.includes('asante') || m.includes('merci') || m.includes('webale')) {
            return responses.responses["thank you"] || "You're welcome!";
        }
        
        // Check for goodbye
        if (m.includes('bye') || m.includes('goodbye') || m.includes('kwaheri') || m.includes('au revoir') || m.includes('weeraba')) {
            return responses.responses["bye"] || "Goodbye! Have a great day!";
        }
        
        // Check for specific keywords
        const keywordMapping = {
            // English keywords
            'verification code': 'verification code',
            'verification': 'verification code',
            'code': 'verification code',
            '1948': 'verification code',
            'forgot': 'forgot password',
            'password reset': 'reset password',
            'reset': 'reset password',
            'social': 'social login',
            'google': 'social login',
            'apple': 'social login',
            'phone login': 'social login',
            'report': 'report issue',
            'complaint': 'report issue',
            'issue': 'report issue',
            'problem': 'report issue',
            'anonymous': 'anonymous report',
            'private': 'anonymous report',
            'track': 'track complaint',
            'status': 'track complaint',
            'progress': 'track complaint',
            'contact': 'contact support',
            'support': 'contact support',
            'help': 'contact support',
            'register': 'account registration',
            'sign up': 'account registration',
            'create account': 'account registration',
            'student id': 'student id format',
            'id format': 'student id format',
            'categories': 'complaint categories',
            'types': 'complaint categories',
            'response': 'response time',
            'time': 'response time',
            'how long': 'response time',
            'emergency': 'emergency',
            'urgent': 'emergency',
            'privacy': 'privacy',
            'data': 'privacy',
            'feature': 'feature request',
            'suggestion': 'feature request',
            'bug': 'bug report',
            'error': 'bug report',
            'working hours': 'working hours',
            'hours': 'working hours',
            'time': 'working hours',
            'export': 'data export',
            'download': 'data export',
            'language': 'language change',
            'translate': 'language change',
            'dark mode': 'dark mode',
            'theme': 'dark mode',
            'mobile': 'mobile app',
            'app': 'mobile app'
        };
        
        // Find matching keyword
        for (const [keyword, responseKey] of Object.entries(keywordMapping)) {
            if (m.includes(keyword)) {
                const response = responses.responses[responseKey];
                if (response) {
                    return response + (responseKey === 'verification code' ? '' : getQuickActions(lang));
                }
            }
        }
        
        // Default response in selected language
        const defaultResponses = {
            en: "I can help you with: verification codes (1948), password reset, reporting issues, tracking complaints, and more. What specifically do you need help with?" + getQuickActions('en'),
            sw: "Naweza kukusaidia kuhusu: nambari za uthibitisho (chaguo-msingi: 1948), kuweka upya nenosiri, kuripoti matatizo, kufuatilia malalamiko, na zaidi. Unahitaji usaidizi gani hasa?" + getQuickActions('sw'),
            fr: "Je peux vous aider avec: codes de vérification (par défaut: 1948), réinitialisation de mot de passe, signalement de problèmes, suivi des plaintes, et plus. De quoi avez-vous besoin spécifiquement?" + getQuickActions('fr'),
            lg: "Nsobola kukuyamba ku: koodi z'okukakasa (ez'okulondoola: 1948), okusalawo password, okubuulira ebizibu, okugoberera ebikolwa, n'ebirala. Kiki ekisoboka okukuyambaako?" + getQuickActions('lg')
        };
        
        return defaultResponses[lang] || defaultResponses.en;
    }

    function getQuickActions(lang) {
        const actions = chatbotResponses[lang]?.quickActions || chatbotResponses.en.quickActions;
        
        if (!actions || actions.length === 0) return '';
        
        let html = '<div class="quick-actions">';
        actions.forEach(action => {
            html += `<button class="quick-action-btn" onclick="quickAction('${action.action}')">${action.label}</button>`;
        });
        html += '</div>';
        
        return html;
    }

    function quickAction(action) {
        let response = generateBotResponse(action);
        addChatMessage('user', action);
        setTimeout(() => {
            addChatMessage('bot', response);
        }, 500);
    }

    function handleChatInput(event) {
        if (event.key === 'Enter') sendChatMessage();
    }

    function updateChatbotLanguage() {
        const lang = currentLanguage || 'en';
        const responses = chatbotResponses[lang] || chatbotResponses.en;
        
        // Update chatbot title
        const title = document.getElementById('chatbotTitle');
        if (title) {
            const titles = {
                en: "CampusVoice Assistant",
                sw: "Msaidizi wa CampusVoice",
                fr: "Assistant CampusVoice",
                lg: "Omuwandiisi wa CampusVoice"
            };
            title.textContent = titles[lang] || titles.en;
        }
        
        // Update placeholder
        const input = document.getElementById('chatbotInput');
        if (input) {
            const placeholders = {
                en: "Ask me about verification codes, reporting issues...",
                sw: "Niulize kuhusu nambari za uthibitisho, kuripoti matatizo...",
                fr: "Demandez-moi des codes de vérification, signalement de problèmes...",
                lg: "Mbuulire ku koodi z'okukakasa, okubuulira ebizibu..."
            };
            input.placeholder = placeholders[lang] || placeholders.en;
        }
    }
async function verifySecurityAnswer() {
    const answer = document.getElementById('securityAnswer').value.trim();
    const btn = document.getElementById('verifyAnswerBtn');
    const errorDiv = document.getElementById('forgotPasswordError');

    if (!answer) {
        errorDiv.innerHTML = '<div class="error-msg">Please enter your answer</div>';
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="loading-spinner"></span> Verifying...';

    try {
        console.log('Verifying security answer for:', forgotPasswordEmail);
        
        // Send as FormData
        const formData = new FormData();
        formData.append('action', 'verify_answer');
        formData.append('email', forgotPasswordEmail);
        formData.append('answer', answer);
        
        const response = await fetch('api/forgot_password.php', {
            method: 'POST',
            body: formData
        });

        console.log('Response status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const text = await response.text();
        console.log('Raw response:', text);
        
        let data;
        try {
            data = JSON.parse(text);
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            throw new Error('Invalid response from server');
        }

        if (data.success) {
            if (data.has_more_questions) {
                // More questions to answer
                console.log('More questions available. Question', data.question_number);
                
                // Clear the answer input
                document.getElementById('securityAnswer').value = '';
                
                // Update the question display
                const securityQuestionEl = document.getElementById('securityQuestionText');
                if (securityQuestionEl) {
                    securityQuestionEl.textContent = data.security_question;
                }
                
                // Show progress message with question number
                errorDiv.innerHTML = `<div class="success-msg">✓ Correct! Question ${data.question_number - 1} of 5. ${data.message}</div>`;
                
                console.log('Security answer verified, moving to next question');
            } else {
                // All questions answered correctly - move to reset password step
                console.log('All security questions answered correctly!');
                
                currentForgotPasswordStep = 3;
                showStep(3);
                errorDiv.innerHTML = '<div class="success-msg">✓ All security questions verified! You can now reset your password.</div>';
            }
        } else {
            errorDiv.innerHTML = `<div class="error-msg">✗ ${data.message || 'Incorrect answer'}</div>`;
            console.log('Security answer verification failed:', data.message);
        }
    } catch (error) {
        console.error('Verify security answer error:', error);
        errorDiv.innerHTML = '<div class="error-msg">Connection error. Please try again.</div>';
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<span id="verifyAnswerText">Verify Answer</span>';
    }
}async function resetPassword() {
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmNewPassword').value;
    const btn = document.getElementById('resetPasswordBtn');
    const errorDiv = document.getElementById('forgotPasswordError');

    if (!newPassword || !confirmPassword) {
        errorDiv.innerHTML = '<div class="error-msg">Please enter both passwords</div>';
        return;
    }

    if (newPassword !== confirmPassword) {
        errorDiv.innerHTML = '<div class="error-msg">Passwords do not match</div>';
        return;
    }

    if (newPassword.length < 6) {
        errorDiv.innerHTML = '<div class="error-msg">Password must be at least 6 characters</div>';
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="loading-spinner"></span> Resetting...';

    try {
        console.log('Resetting password for:', forgotPasswordEmail);
        
        // Send as FormData
        const formData = new FormData();
        formData.append('action', 'reset_password');
        formData.append('email', forgotPasswordEmail);
        formData.append('new_password', newPassword);
        
        const response = await fetch('api/forgot_password.php', {
            method: 'POST',
            body: formData
        });

        console.log('Response status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const text = await response.text();
        console.log('Raw response:', text);
        
        let data;
        try {
            data = JSON.parse(text);
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            throw new Error('Invalid response from server');
        }

        if (data.success) {
            errorDiv.innerHTML = '<div class="success-msg">✓ Password reset successfully! Redirecting to login...</div>';
            console.log('Password reset successfully');
            
            // Redirect to login page after 2 seconds
            setTimeout(() => {
                closeModal();
                switchPage('loginPage');
                resetForgotPassword();
            }, 2000);
        } else {
            errorDiv.innerHTML = `<div class="error-msg">✗ ${data.message || 'Failed to reset password'}</div>`;
            console.log('Password reset failed:', data.message);
        }
    } catch (error) {
        console.error('Reset password error:', error);
        errorDiv.innerHTML = '<div class="error-msg">Connection error. Please try again.</div>';
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<span id="resetPasswordText">Reset Password</span>';
    }
}
    // ========== INITIALIZATION ==========
    document.addEventListener('DOMContentLoaded', () => {
        // Apply saved language
        applyLanguage(currentLanguage);
        const lbl = document.getElementById('langLabel');
        if (lbl) lbl.textContent = currentLanguage.toUpperCase();
        
        // Update chatbot with current language
        updateChatbotLanguage();

        // Apply saved theme
        if (localStorage.getItem('darkMode') === 'true') {
            document.body.classList.add('dark-mode');
            const icon = document.getElementById('themeIcon');
            if (icon) icon.className = 'bi bi-sun-fill';
        }

        // Remember email if checked
        const remembered = localStorage.getItem('rememberEmail');
        const loginForm = document.getElementById('loginForm');
        if (loginForm && remembered) {
            loginForm.email.value = remembered;
            document.getElementById('rememberCheckbox').checked = true;
        }

        // Close modals and dropdowns when clicking outside
        document.addEventListener('click', (e) => {
            const auth = document.getElementById('authModal');
            if (auth && e.target === auth) closeModal();

            const appModal = document.getElementById('appModal');
            if (appModal && e.target === appModal) closeAppModal();

            const ld = document.getElementById('langDropdown');
            const ls = document.querySelector('.language-selector');
            if (ld && ls && !ls.contains(e.target)) closeLanguageDropdown();

            const chatbot = document.querySelector('.chatbot-widget');
            if (chatbotOpen && !chatbot.contains(e.target)) {
                toggleChatbot();
            }
        });

        // Clear chatbot input when modal opens
        const modalButtons = document.querySelectorAll('[onclick*="openModal"]');
        modalButtons.forEach(button => {
            button.addEventListener('click', () => {
                const chatbotInput = document.getElementById('chatbotInput');
                if (chatbotInput) chatbotInput.value = '';
            });
        });
        
        console.log('CampusVoice initialized successfully!');
    });
  </script>
</body>
</html>