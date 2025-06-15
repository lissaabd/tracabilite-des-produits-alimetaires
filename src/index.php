<!DOCTYPE html>
<html>
<head>
	<title>connecter</title>
	<meta charset="utf-8">
	<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css2?family=Nunito&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito">
</head>
<style>

@import url('https://fonts.googleapis.com/css2?family=Nunito:wght@600&display=swap');
@import url('https://fonts.googleapis.com/css2?family=Nunito:wght@800&display=swap');

body {
	color: #ffffff;
	background: url('trace.jpg') no-repeat center center/cover;
    background-attachment: fixed;
	font-family: "Roboto", sans-serif;
    font-optical-sizing: auto;
    font-weight: 600;
    font-style: normal;
	background-color: white;
	display: flex;
	justify-content: center;
	align-items: center;
	height: 100vh;
	flex-direction: column;
}

*{
	box-sizing: border-box;
}

form {
	background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(3px); /* Blurs whatever is behind */
    -webkit-backdrop-filter: blur(3px); /* For Safari */
	box-shadow: 0 0 10px rgba(0, 0, 0, 0.4);
	width: 500px;
	border: 2px solid #ccc;
	padding: 30px;
	border-radius: 15px;
}

h2 {
    text-align: center;
	margin-bottom: 40px;
	font-weight: 800;
	font-size: 45px;
}

input {
	display: block;
	border: 2px solid #ccc;
	width: 95%;
	padding: 10px;
	margin: 10px auto;
	border-radius: 5px;
}

input:hover {
	box-shadow: 0 0 5px #169976;
}

label {
	color: #ffffff;
	font-size: 15px;
	padding: 10px;
}

button {
	font-family: 'Nunito', sans-serif;
	font-weight: 800;
	display: flex;
	justify-content: center;	
	background-color: #169976;
	padding: 10px 15px;
	color: #fff;
	border-radius: 5px;
	margin-right: 10px;
	border: none;
}
button:hover{
	opacity: .7;
}
.error {
   background: #F2DEDE;
   color: #A94442;
   padding: 10px;
   width: 95%;
   border-radius: 5px;
   margin: 20px auto;
}

a {
	float: right;
	background: #555;
	padding: 10px 15px;
	color: #fff;
	border-radius: 5px;
	margin-right: 10px;
	border: none;
	text-decoration: none;
}
a:hover{
	opacity: .7;
}

input::placeholder {
    font-family: 'Nunito', sans-serif;
}

</style>
<body>
     <form action="login.php" method="post">
     	<h2>Connecter</h2>
        
     	<?php if (isset($_GET['error'])) { ?>
     		<p class="error"><?php echo $_GET['error']; ?></p>
     	<?php } ?>

     	<label>Adresse e-mail</label>
     	<input type="text" name="email" placeholder="Votre Adresse e-mail"><br>

     	<label>Mot De Passe</label>
     	<input type="password" name="password" placeholder="Votre Mot De Passe"><br>

     	<button type="submit" >Login</button>
     </form>
</body>
</html>