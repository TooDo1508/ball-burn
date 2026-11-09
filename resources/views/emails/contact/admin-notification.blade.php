<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Liên hệ mới từ website BALLBURN</title>
</head>
<body style="margin: 0; padding: 32px 16px; background: #f5f4f1; color: #171717; font-family: Arial, sans-serif; line-height: 1.65;">
    <main style="max-width: 680px; margin: 0 auto; padding: 32px; background: #ffffff;">
        <p style="margin-top: 0; color: #f36b32; font-size: 13px; font-weight: 700; letter-spacing: 0.08em;">BALLBURN · LIÊN HỆ WEBSITE</p>
        <h1 style="font-size: 24px; line-height: 1.3;">Bạn có liên hệ mới</h1>
        <p><strong>Họ tên:</strong> {{ $contactMessage->name }}</p>
        <p><strong>Email:</strong> <a href="mailto:{{ $contactMessage->email }}">{{ $contactMessage->email }}</a></p>
        @if ($contactMessage->phone)<p><strong>Số điện thoại:</strong> {{ $contactMessage->phone }}</p>@endif
        @if ($contactMessage->subject)<p><strong>Chủ đề:</strong> {{ $contactMessage->subject }}</p>@endif
        <p><strong>Nội dung:</strong></p>
        <div style="white-space: pre-wrap;">{{ $contactMessage->message }}</div>
        <p style="margin-bottom: 0; color: #666666; font-size: 13px;">Trả lời email này sẽ gửi phản hồi trực tiếp đến khách.</p>
    </main>
</body>
</html>
