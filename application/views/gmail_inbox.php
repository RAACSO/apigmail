<!DOCTYPE html>
<html>
<head>
    <title>Gmail Inbox</title>
</head>
<body>
    <h1>Gmail Inbox</h1>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>Snippet</th>
        </tr>
        <?php foreach ($messages as $message): ?>
        <tr>
            <td><?php echo $message->getId(); ?></td>
            <td><?php echo $message->getSnippet(); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>