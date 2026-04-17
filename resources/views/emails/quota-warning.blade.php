<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Предупреждение о квоте</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #f59e0b;">⚠️ Предупреждение о квоте</h1>
        
        <p>Уважаемый пользователь,</p>
        
        <p>Ваш аккаунт (Tenant ID: {{ $tenantId }}) приближается к лимиту ресурса <strong>{{ $resourceType }}</strong>.</p>
        
        <div style="background: #fef3c7; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <p><strong>Текущее использование:</strong> {{ $quotaData['used'] }}</p>
            <p><strong>Лимит:</strong> {{ $quotaData['quota'] }}</p>
            <p><strong>Использовано:</strong> {{ $quotaData['percentage'] }}%</p>
            <p><strong>Осталось:</strong> {{ $quotaData['remaining'] }}</p>
        </div>
        
        <p>Рекомендуем:</p>
        <ul>
            <li>Оптимизировать использование ресурсов</li>
            <li>Рассмотреть upgrading плана подписки</li>
            <li>Связаться с поддержкой для увеличения лимитов</li>
        </ul>
        
        <p>Если лимит будет исчерпан полностью, новые запросы будут отклонены с кодом 429.</p>
        
        <hr style="margin: 20px 0; border: none; border-top: 1px solid #e5e7eb;">
        
        <p style="color: #6b7280; font-size: 12px;">
            Это автоматическое уведомление от CatVRF Platform.<br>
            Пожалуйста, не отвечайте на это письмо.
        </p>
    </div>
</body>
</html>
