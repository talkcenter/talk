<!doctype html>
<html>
  <head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo $title; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1">

    <style>

      body {
        background: #fff;
        margin: 0;
        padding: 0;
        line-height: 1.5;
      }
      body, input, button {
        font-family: 'Open Sans', sans-serif;
        font-size: 16px;
        color: #7E96B3;
      }
      .container {
        min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      max-width: 500px;
      margin: 0 auto;
      text-align: center;
      }
      a {
        color: #e7652e;
        text-decoration: none;
      }
      a:hover {
        text-decoration: underline;
      }

      h1 {
        margin: 0px;
      }
      h2 {
        font-size: 28px;
        font-weight: normal;
        color: #3C5675;
        margin-bottom: 0;
      }

      form {
        margin: 0px;
      }
      .FormBody {
        padding: 0px;
      }
      .FormGroup {
        margin-bottom: 10px;
      }
      .FormButtons {
          margin-top: 15px;
      }
      .FormGroup .FormField:first-child input {
        border-top-left-radius: 4px;
        border-top-right-radius: 4px;
      }
      .FormGroup .FormField:last-child input {
        border-bottom-left-radius: 4px;
        border-bottom-right-radius: 4px;
      }
      .FormField input {
        background: #eff6ff;
        margin: 0 0 1px;
        border: 1px solid transparent;
        transition: background 0.2s, border-color 0.2s, color 0.2s;
        width: 100%;
        padding: 10px 10px 10px 220px;
        box-sizing: border-box;
      }
      .FormField input:focus {
        border-color: #07C160;
        background: #fff;
        color: #444;
        outline: none;
      }
      .FormField label {
        float: left;
        width: 180px;
        text-align: right;
        margin-right: -180px;
        position: relative;
        margin-top: 13px;
        font-size: 13px;
        pointer-events: none;
        opacity: 0.7;
      }
      button {
        width: 100%;
        background: #07c160; 
        color: #fff;
        border: 0;
        font-weight: bold;
        border-radius: 4px;
        cursor: pointer;
        padding: 15px 30px;
        -webkit-appearance: none;
      }
      button[disabled] {
        opacity: 0.5;
      }

      #error {
        background: #D83E3E;
        color: #fff;
        padding: 15px 20px;
        border-radius: 4px;
        margin-bottom: 20px;
      }

      .boxshadow {
       
      }

      .Problems {
        margin-top: 50px;
      }
      .Problems .Problem:first-child {
        border-top-left-radius: 4px;
        border-top-right-radius: 4px;
      }
      .Problems .Problem:last-child {
        border-bottom-left-radius: 4px;
        border-bottom-right-radius: 4px;
      }
      .Problem {
        background: #07C160;
        margin: 0 0 1px;
        padding: 20px 25px;
        text-align: left;
      }
      .Problem-message {
        font-size: 16px;
        color: #FFFFFF;
        font-weight: normal;
        margin: 0;
      }
      .Problem-detail {
        font-size: 13px;
        margin: 5px 0 0;
      }
    </style>
  </head>

  <body>
    <div class="container">
      <div class="boxshadow">
        <h1><?php echo file_get_contents(__DIR__.'/logo.svg'); ?></h1>
        <?php echo $content; ?>
      </div>
    </div>
  </body>
</html>
