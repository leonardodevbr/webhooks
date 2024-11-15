<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>
<body>
<h1>Login</h1>
<form action="{{ route('account.login') }}" method="POST">
    @csrf
    <label for="email">Email:</label>
    <input type="email" name="email" required>

    <label for="password">Senha:</label>
    <input type="password" name="password" required>

    <button type="submit">Entrar</button>
</form>
</body>
</html>
