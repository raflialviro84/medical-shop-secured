<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Forbidden</title>
    <style>
        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #f6f8fa;
            color: #24292e;
            overflow: hidden;
        }

        .scene {
            position: relative;
            width: 100%;
            height: 60vh;
            background: linear-gradient(to bottom, #79b8ff 0%, #c8e1ff 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

        .ground {
            position: absolute;
            bottom: 0;
            width: 100%;
            height: 30%;
            background: #e3d3a4; /* Sand color */
            border-top: 2px solid #d4c28c;
        }

        .content {
            position: relative;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 40px;
            margin-top: 50px;
        }

        .text-group {
            text-align: center;
            position: relative;
        }

        .error-code {
            font-size: 150px;
            font-weight: 900;
            color: #ffffff;
            margin: 0;
            line-height: 1;
            text-shadow: 0px 8px 15px rgba(0,0,0,0.15);
        }

        .speech-bubble {
            background: #ffffff;
            border-radius: 8px;
            padding: 20px 30px;
            font-size: 20px;
            font-weight: 500;
            color: #24292e;
            position: relative;
            margin-top: 20px;
            box-shadow: 0px 4px 10px rgba(0,0,0,0.1);
            max-width: 250px;
            display: inline-block;
        }

        .speech-bubble::before {
            content: '';
            position: absolute;
            right: -15px; /* Arrow pointing right towards character */
            top: 50%;
            transform: translateY(-50%);
            border-width: 15px 0 15px 15px;
            border-style: solid;
            border-color: transparent transparent transparent #ffffff;
        }

        .character {
            width: 200px;
            height: 250px;
            background-color: #2f363d;
            border-radius: 100px 100px 20px 20px;
            position: relative;
            box-shadow: inset -15px -15px 0px rgba(0,0,0,0.2), 0 10px 20px rgba(0,0,0,0.3);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .character-eye {
            width: 40px;
            height: 60px;
            background: #ffffff;
            border-radius: 50%;
            margin: 0 10px;
            position: relative;
        }

        .character-eye::after {
            content: '';
            position: absolute;
            width: 15px;
            height: 25px;
            background: #d73a49; /* Red eyes for forbidden */
            border-radius: 50%;
            top: 15px;
            right: 10px;
        }

        .bottom-section {
            height: 40vh;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .search-box {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .search-box button, .btn-home {
            background-color: #fafbfc;
            color: #24292e;
            border: 1px solid #d1d5da;
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            font-weight: 500;
            transition: 0.2s ease-in-out;
            text-decoration: none;
        }

        .search-box button:hover, .btn-home:hover {
            background-color: #f3f4f6;
            border-color: #a7a9ac;
        }

        .links {
            color: #586069;
            font-size: 14px;
        }
        
        .links a {
            color: #0366d6;
            text-decoration: none;
            margin: 0 10px;
        }

        .links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="scene">
        <div class="ground"></div>
        <div class="content">
            <div class="text-group">
                <h1 class="error-code">403</h1>
                <div class="speech-bubble">
                    You don't have permission to access this resource.
                </div>
            </div>
            
            <div class="character">
                <div class="character-eye"></div>
                <div class="character-eye"></div>
            </div>
        </div>
    </div>

    <div class="bottom-section">
        <p style="color: #586069; margin-bottom: 15px;">Access to this page is restricted.</p>
        <div class="search-box">
            <a href="{{ url('/') }}" class="btn-home">Return to Homepage</a>
        </div>
        
        <div class="links">
            <a href="#">Contact Support</a> — <a href="#">Status</a> — <a href="#">Services</a>
        </div>
    </div>

</body>
</html>
