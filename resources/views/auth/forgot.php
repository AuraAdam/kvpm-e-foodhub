<section class="auth">
  <div class="auth__card">
    <h1>Lupa kata laluan</h1>
    <p>E-FoodHub belum menghantar e-mel, jadi kata laluan tidak boleh ditetapkan semula secara automatik.</p>
    <div class="panel panel--tight mt-4">
      <h2 style="font-size:1rem">Cara mendapatkan semula akses</h2>
      <ol class="mb-0 small">
        <li>Hubungi pentadbir E-FoodHub secara bersemuka atau melalui <?= e(setting('contact_email')) ?>.</li>
        <li>Pentadbir akan mengesahkan identiti anda dan memberikan kata laluan sementara.</li>
        <li>Log masuk dengan kata laluan sementara itu. Anda akan diminta menukarnya serta-merta.</li>
      </ol>
    </div>
    <a class="btn btn--block mt-4" href="<?= e(url('/login')) ?>">Kembali ke log masuk</a>
  </div>
</section>
