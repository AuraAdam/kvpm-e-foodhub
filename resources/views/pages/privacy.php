<div class="container page" style="max-width:820px">
  <nav class="breadcrumbs" aria-label="Laluan"><a href="<?= e(url('/')) ?>">Laman Utama</a><?= icon('chevron-right', 14) ?><span aria-current="page">Notis privasi</span></nav>
  <h1>Notis privasi</h1>
  <div class="alert alert--info mt-4"><?= icon('info', 18) ?><div class="alert__body">Draf notis untuk projek akademik. Ia belum disemak oleh pihak kolej.</div></div>
  <div class="panel mt-4">
    <h2>Data yang kami simpan</h2>
    <ul>
      <li>Nama, e-mel dan nombor telefon semasa pendaftaran.</li>
      <li>Butiran pesanan: produk, kuantiti, nama dan telefon penerima, nota pesanan.</li>
      <li>Rekod bayaran simulasi dan tunai (tanpa sebarang maklumat kad atau bank).</li>
      <li>Kata laluan disimpan dalam bentuk <em>hash</em> sehala dan tidak boleh dibaca oleh sesiapa.</li>
    </ul>
    <h2>Siapa boleh melihatnya</h2>
    <ul class="mb-0">
      <li>Penjual hanya melihat nama, telefon dan nota bagi pesanan yang dibuat di kedai mereka.</li>
      <li>Pentadbir boleh melihat rekod untuk menyelesaikan aduan dan memantau penyalahgunaan. Tindakan penting pentadbir direkodkan dalam log audit.</li>
      <li>Data tidak dikongsi dengan pihak luar.</li>
    </ul>
  </div>
</div>
