<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />
		<title>Message from <?php echo $_POST["shop_name"]; ?></title>
		<style>	@media only screen and (max-width: 300px){
				body {
					width:218px !important;
					margin:auto !important;
				}

				thead, tbody{width: 100%}
				.table {width:195px !important;margin:auto !important;}
				.logo, .titleblock, .linkbelow, .box, .footer, .space_footer{width:auto !important;display: block !important;}
				span.title{font-size:20px !important;line-height: 23px !important}
				span.subtitle{font-size: 14px !important;line-height: 18px !important;padding-top:10px !important;display:block !important;}
				td.box p{font-size: 12px !important;font-weight: bold !important;}
				.table-recap table, .table-recap thead, .table-recap tbody, .table-recap th, .table-recap td, .table-recap tr {
				display: block !important;
				}
				.table-recap{width: 200px!important;}
				.table-recap tr td, .conf_body td{text-align:center !important;}
				.address{display: block !important;margin-bottom: 10px !important;}
				.space_address{display: none !important;}
			}

	@media only screen and (min-width: 301px) and (max-width: 500px) {
				body {width:425px!important;margin:auto!important;}
				thead, tbody{width: 100%}
				.table {margin:auto!important;}
				.logo, .titleblock, .linkbelow, .box, .footer, .space_footer{width:auto!important;display: block!important;}
				.table-recap{width: 295px !important;}
				.table-recap tr td, .conf_body td{text-align:center !important;}
				.table-recap tr th{font-size: 10px !important}
	}

	@media only screen and (min-width: 501px) and (max-width: 768px) {
				body {width:478px!important;margin:auto!important;}
				thead, tbody{width: 100%}
				.table {margin:auto!important;}
				.logo, .titleblock, .linkbelow, .box, .footer, .space_footer{width:auto!important;display: block!important;}
	}

	@media only screen and (max-device-width: 480px) {
				body {width:340px!important;margin:auto!important;}
				thead, tbody{width: 100%}
				.table {margin:auto!important;}
				.logo, .titleblock, .linkbelow, .box, .footer, .space_footer{width:auto!important;display: block!important;}
				.table-recap{width: 295px!important;}
				.table-recap tr td, .conf_body td{text-align:center!important;}
				.address{display: block !important;margin-bottom: 10px !important;}
				.space_address{display: none !important;}
		}
</style>
</head>

	<body style="-webkit-text-size-adjust:none;background-color:#fff;width:650px;font-family:Open-sans, sans-serif;color:#555454;font-size:13px;line-height:18px;margin:auto">
		<table class="table table-mail" style="width:100%;margin-top:10px;-moz-box-shadow:0 0 5px #afafaf;-webkit-box-shadow:0 0 5px #afafaf;-o-box-shadow:0 0 5px #afafaf;box-shadow:0 0 5px #afafaf;filter:progid:DXImageTransform.Microsoft.Shadow(color=#afafaf,Direction=134,Strength=5)">
			<tr>
				<td class="space" style="width:20px;padding:7px 0">&nbsp;</td>
				<td align="center" style="padding:7px 0">
					<table class="table" bgcolor="#ffffff" style="width:100%">
						<tr>
							<td align="center" class="logo" style="border-bottom:4px solid #333333;padding:7px 0">
								<a title="<?php echo $_POST["shop_name"]; ?>" href="<?php echo $_POST["shop_url"]; ?>" style="color:#337ff1">
									<img src="<?php echo $_POST["shop_logo"]; ?>" alt="<?php echo $_POST["shop_name"]; ?>" />
								</a>
							</td>
						</tr>
			<tr>
				<td align="center" class="titleblock" style="padding:7px 0">
					<font size="2" face="Open-sans, sans-serif" color="#555454">
						<span class="title" style="font-weight:500;font-size:28px;text-transform:uppercase;line-height:33px">Hi <?php echo $_POST["user_name"]; ?>,</span><br/>
						<span class="subtitle" style="font-weight:500;font-size:16px;text-transform:uppercase;line-height:25px">Terima kasih telah berbelanja di <?php echo $_POST["shop_name"]; ?>!</span>
					</font>
				</td>
			</tr>

			<tr>
				<td class="space_footer" style="padding:0!important">&nbsp;</td>
			</tr>

			<tr>
				<td class="box" style="background-color:#fbfbfb;border:1px solid #d6d4d4!important;padding:10px!important">
					<p data-html-only="1" style="margin:3px 0 7px;text-transform:uppercase;font-weight:500;font-size:18px;border-bottom:1px solid #d6d4d4!important;padding-bottom:10px">
						Order <?php echo $_POST["order_name"]; ?>&nbsp;-&nbsp;Menunggu pembayaran
					</p>
					<span style="color:#777">
						Pesanan Anda telah berhasil diproses dan <strong>akan dikirimkan setelah pembayaran kami terima</strong>.			<!-- <span style="color:#333"><strong>{description}</strong></span> <br /> -->
					</span>
				</td>
			</tr>

			<tr>
				<td class="space_footer" style="padding:0!important;border:none">&nbsp;</td>
			</tr>

			<tr>
				<td class="box" style="background-color:#fbfbfb;border:1px solid #d6d4d4!important;padding:10px!important">
					<p style="margin:3px 0 7px;text-transform:uppercase;font-weight:500;font-size:18px;border-bottom:1px solid #d6d4d4!important;padding-bottom:10px">
						Anda telah memilih untuk membayar menggunakan CIMB Niaga Virtual Account.
					</p>
					<span style="color:#777">
						Berikut adalah informasi pembayaran yang harus dilakukan :<br /> <br />
						<span style="color:#333">Jumlah Pembayaran : </span><strong><?php echo "Rp" . number_format($_POST["amt"]); ?></strong><br />
						<span style="color:#333">Bank Penerima : </span><strong><?php echo $_POST["bankName"]; ?></strong><br />
						<span style="color:#333">Nomor Virtual Account : </span><strong><?php echo $_POST["vacctNo"]; ?></strong><br />
						<span style="color:#333">Batas Waktu Penerimaan : </span><strong><?php echo $_POST["expDate"]; ?></strong>
					</span>
				</td>
			</tr>
			<tr>
				<td class="space_footer" style="padding:0!important;border:none">&nbsp;</td>
			</tr>

			<tr>
				<td class="box" style="background-color:#fbfbfb;border:1px solid #d6d4d4!important;padding:10px!important">
					<!-- <p style="margin:3px 0 7px;text-transform:uppercase;font-weight:500;font-size:18px;border-bottom:1px solid #d6d4d4!important;padding-bottom:10px">
						You have selected to pay by {bankCd} Virtual Account.</p> -->
					Silakan melakukan pembayaran dengan Bank Transfer <b>CIMB Niaga (Virtual Account)</b> dengan mengikuti petunjuk berikut :<br/><br/>

					<b>ATM CIMB Niaga</b><br/>
					<ol>
						<li>Pilih menu "Pembayaran"</li>
						<li>Pilih menu "Lanjut"</li>
						<li>Pilih menu "Virtual Account"</li>
						<li>Masukkan Nomor Virtual Account, misal 5919XXXXXXXXXXXX</li>
						<li>Pilih "Proses"</li>
						<li>Informasi VA akan ditampilkan, kemudian Klik "Proses"</li>
						<li>Ambil bukti pembayaran Anda dan transaksi selesai</li>
					</ol>
					<b>Mobile Banking</b><br/>
					<ol>
						<li>Login Go Mobile</li>
						<li>Pilih Menu "Transfer"</li>
						<li>Pilih Menu "Rekening Ponsel / CIMB Niaga Lain"</li>
						<li>Pilih Sumber Dana yang akan digunakan</li>
						<li>Pilih "Casa"</li>
						<li>Masukkan Nomor Virtual Account, misal. 5919XXXXXXXXXXXX</li>
						<li>Masukkan Nominal, misal. 10000</li>
						<li>Klik "Lanjut"</li>
						<li>Informasi VA akan ditampilkan, kemudian Masukkan "PIN Mobile"</li>
						<li>Klik "Konfirmasi"</li>
						<li>Bukti pembayaran akan dikirim melalui sms</li>
						<li>Transaksi selesai</li>
					</ol>
					<b>Internet Banking</b><br/>
					<ol>
						<li>Login Internet Banking</li>
						<li>Pilih menu "Bayar Tagihan"</li>
						<li>Rekening Sumber - Pilih yang akan Anda digunakan</li>
						<li>Jenis Pembayaran - Pilih "Virtual Account"</li>
						<li>Untuk Pembayaran - Pilih "Masukkan nomor virtual account"</li>
						<li>Masukkan Nomor Virtual Account, misal. 5919XXXXXXXXXXXX</li>
						<li>Isi "Remark" Jika diperlukan</li>
						<li>Klik "Lanjut"</li>
						<li>Informasi VA akan ditampilkan, kemudian Masukkan "mPIN"</li>
						<li>Klik "Kirim"</li>
						<li>Bukti transaksi akan ditampilkan</li>
						<li>Transaksi selesai</li>
					</ol>
					<font color='red'>*1 (Satu) Nomor Virtual Account hanya berlaku untuk 1 (Satu) Nomor Pesanan</font>
				</td>
			</tr>

			<tr>
				<td class="space_footer" style="padding:0!important">&nbsp;</td>
			</tr>

			<tr>
					<td class="space_footer" style="padding:0!important">&nbsp;</td>
			</tr>
		</table>

	</td>
				<td class="space" style="width:20px;padding:7px 0">&nbsp;</td>
	</tr>
	</table>
</body>
</html>
