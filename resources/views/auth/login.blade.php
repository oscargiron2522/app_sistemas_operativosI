<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión · LinuxCore</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/linuxcore.css') }}" rel="stylesheet">
</head>
<body>
<div class="login-box">
    <div class="text-center mb-3">
        <div class="lc-ln d-inline-flex align-items-center justify-content-center" style="width:58px;height:58px;border-radius:50%;border:3px solid #0b2a4a;border-top-color:#1769ff;border-right-color:#1769ff;font-weight:800;font-size:21px;color:#0b2a4a">LN</div>
        <div class="fs-3 fw-bold" style="color:#0b2a4a">Linux<span style="color:#1769ff">Core</span></div>
    </div>
    <div class="lc-card">
        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-semibold">Usuario</label>
                <input type="text" name="usuario" value="{{ old('usuario') }}" class="form-control @error('usuario') is-invalid @enderror" autofocus required>
                @error('usuario')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Contraseña</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button class="btn btn-lc w-100">Ingresar</button>
        </form>
    </div>
</div>
</body>
</html>
