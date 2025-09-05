<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="format-detection" content="telephone=no,address=no,email=no,date=no,url=no">
    <title>{{ $processedTemplate['subject'] ?? 'Email' }}</title>
    <style>
        body {
            font-family: {{ $template->design_config['font_family'] ?? 'Arial, sans-serif' }};
            line-height: 1.6;
            color: {{ $template->design_config['text_color'] ?? '#333' }};
            margin: 0;
            padding: 20px;
            background-color: {{ $template->design_config['background_color'] ?? '#f4f4f4' }};
        }
        .container {
            max-width: {{ $template->design_config['max_width'] ?? '600px' }};
            margin: 0 auto;
            background-color: white;
            padding: 0;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
    </style>
</head>
<body>
    <div class="container">
        {!! $processedTemplate['body'] ?? 'No hay contenido disponible.' !!}
    </div>
</body>
</html>