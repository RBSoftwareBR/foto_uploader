<form id="formulario">
    <input type="text" name="nome" placeholder="Nome">

    <input
        type="file"
        id="foto"
        name="foto"
        accept="image/*"
        capture="environment"
        required
    >

    <button type="submit">Salvar</button>
</form>

<script>
document.getElementById('formulario').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    const response = await fetch('upload_foto.php', {
        method: 'POST',
        body: formData
    });

    const result = await response.json();

    if (result.success) {
        console.log('URL da foto:', result.url);

        // Aqui você salva junto com o restante do formulário
        alert('Foto enviada: ' + result.url);
    } else {
        alert(result.error);
    }
});
</script>
