<!DOCTYPE html>
<html>
<head>
    <title>Gmail Inbox</title>
</head>
<body>
    <h1>Gmail Inbox</h1>
    <a href="<?php echo site_url('gmail/compose'); ?>">Redactar Correo</a>
    <table border="1">
        <tr>
            <th>Remitente</th>
            <th>Asunto</th>
            <th>Vista Previa</th>
        </tr>
        <?php foreach ($messages as $message): ?>
        <tr>
            <td><?php echo htmlspecialchars($message['from']); ?></td>
            <td><?php echo htmlspecialchars($message['subject']); ?></td>
            <td><?php echo htmlspecialchars($message['snippet']); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
