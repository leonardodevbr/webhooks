<!DOCTYPE html>
<html>
<head>
    <title>Registro</title>
</head>
<body>
<h1>Registro</h1>
<form action="{{ route('account.register') }}" method="POST">
    @csrf
    <label for="name">Nome:</label>
    <input type="text" name="name" required>

    <label for="slug">Slug:</label>
    <input type="text" name="slug" required>

    <label for="email">Email:</label>
    <input type="email" name="email" required>

    <label for="password">Senha:</label>
    <input type="password" name="password" required>

    <label for="password_confirmation">Confirme a senha:</label>
    <input type="password" name="password_confirmation" required>

    <button type="submit">Registrar</button>
</form>
</body>
</html>
