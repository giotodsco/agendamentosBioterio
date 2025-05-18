<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/acexx/front-end/Estilos/style_adm.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <title>Bioterio - Login</title>
</head>
<style>
    <style>
    * {
    color: rgb(60, 59, 59);
    font-family: Georgia, 'Times New Roman', Times, serif;
}
#img_bioterio {
    background-image:
    radial-gradient(circle, rgba(121, 125, 125, 0.43) 0%, rgba(101, 113, 98, 0.626) 31%, rgba(64,122,53,0.36) 85%),
    url('https://www.fsa.br/wp-content/uploads/2019/02/d79abec1-2674-42b2-9873-431fbdaa9007.jpg');
    width: 50%;
    height: 100vh;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    border-radius: 1px 50px 50px 1px;
}
#login {
    background: radial-gradient(circle, rgba(173,199,205,1) 0%, rgba(169,189,165,1) 31%, rgba(64, 122, 53, 0.819) 85%);
}
#login_um {
    display: flex;
}
#login_bioterio {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    text-align: center;
    width: 50%;
}
#info_login {
    width: 45%;
    background-color: rgb(225, 225, 228);
    height:70%;
    border-radius: 5%;
    box-shadow: 5px 5px 50px rgba(90, 90, 90, 0.392);

}
#titulo {
    color: rgb(55, 75, 51);
    font-size: 32px;
    padding: 24px;
    text-align: center;
    font-weight: 700;
}
.user_senha {
    font-size: 20px;
    padding: 20px;
}
input {
    width: 80%;
    padding: 10px;
    border: none;
    border-radius: 10px;
}
.senha {
    padding-top: 12px;
    padding-bottom: 30px;
    color: rgb(81, 81, 81);
}
#cadastro_aqui {
    color: rgb(55, 75, 51);
    text-decoration:underline;
    font-weight: 600;
}
.voltar  {
    padding-top: 18px;
    text-decoration: none;
    font-size: 18px;
}
#funcionarios {
    font-size: 16px;
    padding-top: 18px;
}
#btn_entrar {
    width: 50%;
    height: 40px;
    background-color: rgba(64, 122, 53, 0.819);
    border: none;
    border-radius: 10px;
    color: white;
    font-size: 20px;
    cursor:pointer;
}
#btn_entrar:hover {
    background-color: rgba(44, 81, 36, 0.819);
}
#btn_entrar:active {
    background-color: rgba(35, 65, 29, 0.819);
}
#mensage {
    color: red;
    font-weight: bold;
    margin-bottom: 10px;
}
@media (max-width: 1024px) {
    #img_bioterio {
        display: none;
    }

    #login_bioterio {
        width: 100%;
    }

    #info_login {
        width: 80%;
    }
}
</style>
<body>
    <div id="login">
        <div id="login_um">
            <div id="img_bioterio">
            </div>
            <div id="login_bioterio">
                <div id="info_login">
                    <p id="titulo">Biotério - FSA</p>

                    <form action="/acexx/back-end/login.php" method="POST">
                        <p class="user_senha" id="ra">Digite seu RA:</p>
                        <input class = "inputs"type="text" name="ra" required>
                        <p class="user_senha" id="senha">Digite sua senha:</p>
                        <input class = "inputs"type="password" name="senha" required>
                        <br>
                        <p id="mensage">
                            <?php
                            // Exibe mensagem de erro se houver
                            if (isset($_GET['erro_login'])) {
                                echo "RA ou senha incorretos!";
                            }
                            ?>
                        </p>
                        <button type="submit" id="btn_entrar">Entrar</button>
                    </form>

                    <p class = "senha"><a class = "senha" href=""></a></p>
                    <br>
                    <p>Precisa de ajuda? <a href="pag_ajuda.html" id="cadastro_aqui">Clique aqui</a></p>
                    <p class="voltar"><a href="pag_inicial.html" class="voltar"><i class="fa-solid fa-arrow-left"></i> Voltar</a></p>
                </div>
                <div id="login_senha">

                </div>
            </div>
        </div>
    </div>

    </body>
</html>