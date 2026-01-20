<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - Khan Invoice</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 60px 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .lock-icon {
            font-size: 80px;
            margin-bottom: 20px;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        h1 {
            color: #333;
            font-size: 32px;
            margin-bottom: 15px;
            font-weight: 700;
        }

        .subtitle {
            color: #667eea;
            font-size: 18px;
            margin-bottom: 20px;
            font-weight: 600;
        }

        p {
            color: #666;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 14px 30px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s ease;
            display: inline-block;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #f5f5f5;
            color: #333;
        }

        .btn-secondary:hover {
            background: #e0e0e0;
            transform: translateY(-2px);
        }

        .emoji {
            display: inline-block;
            animation: wave 1.5s infinite;
            transform-origin: 70% 70%;
        }

        @keyframes wave {
            0%, 100% {
                transform: rotate(0deg);
            }
            10%, 30% {
                transform: rotate(-10deg);
            }
            20%, 40% {
                transform: rotate(10deg);
            }
        }

        .user-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 14px;
            color: #666;
        }

        .user-info strong {
            color: #333;
        }

        @media (max-width: 480px) {
            .container {
                padding: 40px 25px;
            }

            h1 {
                font-size: 26px;
            }

            .subtitle {
                font-size: 16px;
            }

            .buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="lock-icon">🔒</div>
        <h1>Oops! Access Denied</h1>
        <div class="subtitle">Admin Area <span class="emoji">👋</span></div>

        @auth
        <div class="user-info">
            Logged in as: <strong>{{ auth()->user()->name }}</strong><br>
            Role: <strong>{{ ucfirst(auth()->user()->role) }}</strong>
        </div>
        @endauth

        <p>
            This area is reserved for platform administrators only.
            You don't have permission to access this page, but don't worry -
            you still have full access to all your business features!
        </p>

        <div class="buttons">
            <a href="/app" class="btn btn-primary">
                Go to Dashboard
            </a>
            @auth
            <form action="/logout" method="POST" style="display: inline;">
                @csrf
                <button type="submit" class="btn btn-secondary">
                    Logout
                </button>
            </form>
            @endauth
        </div>
    </div>
</body>
</html>
