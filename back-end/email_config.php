<?php
// acexx/back-end/email_config.php
require_once 'phpmailer/src/Exception.php';
require_once 'phpmailer/src/PHPMailer.php';
require_once 'phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $mail;
    
    public function __construct() {
        $this->mail = new PHPMailer(true);
        $this->configurarSMTP();
    }
    
    private function configurarSMTP() {
        try {
            // CREDENCIAIS DE EMAIL
            $this->mail->isSMTP();
            $this->mail->Host = 'smtp.gmail.com';
            $this->mail->SMTPAuth = true;
            $this->mail->Username = 'contatobiodiversidadefsabr@gmail.com'; // SEU EMAIL
            $this->mail->Password = 'haqrympwxiopuqcd'; // SUA SENHA DE APP
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mail->Port = 587;
            $this->mail->CharSet = 'UTF-8';
            
            // Configurações do remetente
            $this->mail->setFrom('contatobiodiversidadefsabr@gmail.com', 'Espaço de Biodiversidade FSA');
            
        } catch (Exception $e) {
            error_log("Erro na configuração SMTP: " . $e->getMessage());
        }
    }
    
    public function enviarEmail($destinatario, $nomeDestinatario, $assunto, $corpoHTML, $corpoTexto = '') {
        try {
            // Limpar destinatários anteriores
            $this->mail->clearAddresses();
            $this->mail->clearAttachments();
            
            // Configurar destinatário
            $this->mail->addAddress($destinatario, $nomeDestinatario);
            
            // Configurar conteúdo
            $this->mail->isHTML(true);
            $this->mail->Subject = $assunto;
            $this->mail->Body = $corpoHTML;
            $this->mail->AltBody = $corpoTexto ?: strip_tags($corpoHTML);
            
            // Enviar
            $resultado = $this->mail->send();
            
            if ($resultado) {
                error_log("Email enviado com sucesso para: $destinatario");
                return ['sucesso' => true, 'mensagem' => 'Email enviado com sucesso'];
            } else {
                error_log("Falha ao enviar email para: $destinatario");
                return ['sucesso' => false, 'mensagem' => 'Falha ao enviar email'];
            }
            
        } catch (Exception $e) {
            error_log("Erro ao enviar email: " . $e->getMessage());
            return ['sucesso' => false, 'mensagem' => 'Erro ao enviar email: ' . $e->getMessage()];
        }
    }
    
    // Templates de email
    public function emailCadastroSucesso($nome, $email, $tipoUsuario = 'usuario') {
        $assunto = "Cadastro realizado com sucesso - Espaço de Biodiversidade FSA";
        
        $corpoHTML = $this->getTemplateHTML([
            'titulo' => 'Cadastro Realizado com Sucesso!',
            'nome' => $nome,
            'mensagem_principal' => $tipoUsuario === 'empresa' 
                ? 'Sua empresa foi cadastrada com sucesso no sistema!'
                : 'Você foi cadastrado(a) com sucesso no sistema!',
            'instrucoes' => [
                'Agora você pode fazer login com suas credenciais',
                $tipoUsuario === 'empresa' 
                    ? 'Lembre-se: agendamentos de empresas passam por análise administrativa'
                    : 'Seus agendamentos individuais são confirmados automaticamente',
                'Acesse o sistema e faça seu primeiro agendamento'
            ],
            'tipo' => 'sucesso'
        ]);
        
        return $this->enviarEmail($email, $nome, $assunto, $corpoHTML);
    }
    
    public function emailAgendamentoConfirmado($nome, $email, $dataAgendamento, $horaAgendamento, $agendamentoId) {
        $assunto = "Agendamento Confirmado - Espaço de Biodiversidade FSA";
        
        $dataFormatada = date('d/m/Y', strtotime($dataAgendamento));
        $horaFormatada = date('H:i', strtotime($horaAgendamento));
        
        $corpoHTML = $this->getTemplateHTML([
            'titulo' => 'Agendamento Confirmado!',
            'nome' => $nome,
            'mensagem_principal' => 'Seu agendamento foi confirmado automaticamente.',
            'detalhes' => [
                'ID do Agendamento' => "#$agendamentoId",
                'Data' => $dataFormatada,
                'Horário' => $horaFormatada
            ],
            'instrucoes' => [
                'Compareça no local na data e horário marcados',
                'Traga um documento de identificação',
                'Em caso de necessidade de cancelamento, acesse o sistema'
            ],
            'tipo' => 'confirmacao'
        ]);
        
        return $this->enviarEmail($email, $nome, $assunto, $corpoHTML);
    }
    
    public function emailAgendamentoPendente($nome, $email, $dataAgendamento, $horaAgendamento, $agendamentoId, $quantidadePessoas = 1) {
        $assunto = "Solicitação de Agendamento Recebida - Espaço de Biodiversidade FSA";
        
        $dataFormatada = date('d/m/Y', strtotime($dataAgendamento));
        $horaFormatada = date('H:i', strtotime($horaAgendamento));
        
        $corpoHTML = $this->getTemplateHTML([
            'titulo' => 'Solicitação Recebida!',
            'nome' => $nome,
            'mensagem_principal' => 'Sua solicitação de agendamento foi recebida e está em análise.',
            'detalhes' => [
                'ID da Solicitação' => "#$agendamentoId",
                'Data Solicitada' => $dataFormatada,
                'Horário Solicitado' => $horaFormatada,
                'Quantidade de Pessoas' => $quantidadePessoas
            ],
            'instrucoes' => [
                'Sua solicitação será analisada pela administração',
                'Você receberá um email com a resposta em até 2 dias úteis',
                'Acompanhe o status pelo sistema'
            ],
            'tipo' => 'pendente'
        ]);
        
        return $this->enviarEmail($email, $nome, $assunto, $corpoHTML);
    }
    
    public function emailAgendamentoAprovado($nome, $email, $dataAgendamento, $horaAgendamento, $agendamentoId, $quantidadePessoas = 1) {
        $assunto = "Agendamento APROVADO - Espaço de Biodiversidade FSA";
        
        $dataFormatada = date('d/m/Y', strtotime($dataAgendamento));
        $horaFormatada = date('H:i', strtotime($horaAgendamento));
        
        $corpoHTML = $this->getTemplateHTML([
            'titulo' => 'Agendamento Aprovado!',
            'nome' => $nome,
            'mensagem_principal' => 'Sua solicitação de agendamento foi APROVADA pela administração!',
            'detalhes' => [
                'ID do Agendamento' => "#$agendamentoId",
                'Data Confirmada' => $dataFormatada,
                'Horário Confirmado' => $horaFormatada,
                'Quantidade de Pessoas' => $quantidadePessoas
            ],
            'instrucoes' => [
                'Compareçam no local na data e horário confirmados',
                'Tragam documentos de identificação para todos os participantes',
                'Em caso de necessidade de cancelamento, avisem com antecedência'
            ],
            'tipo' => 'aprovado'
        ]);
        
        return $this->enviarEmail($email, $nome, $assunto, $corpoHTML);
    }
    
    public function emailAgendamentoNegado($nome, $email, $dataAgendamento, $horaAgendamento, $agendamentoId, $motivo = '') {
        $assunto = "Solicitação de Agendamento Negada - Espaço de Biodiversidade FSA";
        
        $dataFormatada = date('d/m/Y', strtotime($dataAgendamento));
        $horaFormatada = date('H:i', strtotime($horaAgendamento));
        
        $instrucoes = [
            'Você pode fazer uma nova solicitação para outra data',
            'Verifique os horários disponíveis no sistema',
            'Entre em contato conosco em caso de dúvidas'
        ];
        
        if ($motivo) {
            array_unshift($instrucoes, "Motivo: $motivo");
        }
        
        $corpoHTML = $this->getTemplateHTML([
            'titulo' => 'Solicitação Negada',
            'nome' => $nome,
            'mensagem_principal' => 'Infelizmente sua solicitação de agendamento não pôde ser aprovada.',
            'detalhes' => [
                'ID da Solicitação' => "#$agendamentoId",
                'Data Solicitada' => $dataFormatada,
                'Horário Solicitado' => $horaFormatada
            ],
            'instrucoes' => $instrucoes,
            'tipo' => 'negado'
        ]);
        
        return $this->enviarEmail($email, $nome, $assunto, $corpoHTML);
    }
    
    public function emailAgendamentoCancelado($nome, $email, $dataAgendamento, $horaAgendamento, $agendamentoId, $tipoUsuario = 'usuario') {
        $assunto = "Agendamento Cancelado - Espaço de Biodiversidade FSA";
        
        $dataFormatada = date('d/m/Y', strtotime($dataAgendamento));
        $horaFormatada = date('H:i', strtotime($horaAgendamento));
        
        $corpoHTML = $this->getTemplateHTML([
            'titulo' => 'Agendamento Cancelado',
            'nome' => $nome,
            'mensagem_principal' => 'Seu agendamento foi cancelado.',
            'detalhes' => [
                'ID do Agendamento' => "#$agendamentoId",
                'Data' => $dataFormatada,
                'Horário' => $horaFormatada
            ],
            'instrucoes' => [
                'O agendamento foi removido da sua agenda',
                'Você pode fazer um novo agendamento quando desejar',
                'Acesse o sistema para agendar novamente'
            ],
            'tipo' => 'cancelado'
        ]);
        
        return $this->enviarEmail($email, $nome, $assunto, $corpoHTML);
    }
    
    public function emailAgendamentoConcluido($nome, $email, $dataAgendamento, $horaAgendamento, $agendamentoId, $tipoUsuario = 'usuario') {
        $assunto = "Visita Concluída - Espaço de Biodiversidade FSA";
        
        $dataFormatada = date('d/m/Y', strtotime($dataAgendamento));
        
        $mensagemPrincipal = $tipoUsuario === 'empresa' 
            ? 'A visita da sua empresa ao Espaço de Biodiversidade foi concluída com sucesso!'
            : 'Sua visita ao Espaço de Biodiversidade foi concluída com sucesso!';
        
        $corpoHTML = $this->getTemplateHTML([
            'titulo' => 'Visita Concluída!',
            'nome' => $nome,
            'mensagem_principal' => $mensagemPrincipal,
            'detalhes' => [
                'ID do Agendamento' => "#$agendamentoId",
                'Data da Visita' => $dataFormatada
            ],
            'instrucoes' => [
                'Obrigado por visitar nosso espaço!',
                'Esperamos que tenham aproveitado a experiência',
                'Vocês são sempre bem-vindos para futuras visitas'
            ],
            'tipo' => 'concluido'
        ]);
        
        return $this->enviarEmail($email, $nome, $assunto, $corpoHTML);
    }
    
    private function getTemplateHTML($dados) {
        $titulo = $dados['titulo'];
        $nome = $dados['nome'];
        $mensagemPrincipal = $dados['mensagem_principal'];
        $detalhes = $dados['detalhes'] ?? [];
        $instrucoes = $dados['instrucoes'] ?? [];
        $tipo = $dados['tipo'];
        
        // Cores baseadas no tipo
        $cores = [
            'sucesso' => ['primary' => '#28a745', 'bg' => '#d4edda'],
            'confirmacao' => ['primary' => '#407a35', 'bg' => '#e8f5e8'],
            'pendente' => ['primary' => '#ffc107', 'bg' => '#fff3cd'],
            'aprovado' => ['primary' => '#28a745', 'bg' => '#d4edda'],
            'negado' => ['primary' => '#dc3545', 'bg' => '#f8d7da'],
            'cancelado' => ['primary' => '#fd7e14', 'bg' => '#ffe8d1'],
            'concluido' => ['primary' => '#6f42c1', 'bg' => '#e7e3ff']
        ];
        
        $cor = $cores[$tipo] ?? $cores['sucesso'];
        
        $detalhesHTML = '';
        if (!empty($detalhes)) {
            $detalhesHTML = '<div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">';
            foreach ($detalhes as $label => $valor) {
                $detalhesHTML .= "<p style='margin: 8px 0; font-size: 16px;'><strong>$label:</strong> $valor</p>";
            }
            $detalhesHTML .= '</div>';
        }
        
        $instrucoesHTML = '';
        if (!empty($instrucoes)) {
            $instrucoesHTML = '<div style="margin: 20px 0;"><h3 style="color: ' . $cor['primary'] . '; margin-bottom: 15px;">Informações Importantes:</h3><ul style="padding-left: 20px;">';
            foreach ($instrucoes as $instrucao) {
                $instrucoesHTML .= "<li style='margin: 8px 0; font-size: 15px; line-height: 1.5;'>$instrucao</li>";
            }
            $instrucoesHTML .= '</ul></div>';
        }
        
        return "
        <!DOCTYPE html>
        <html lang='pt-br'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>$titulo</title>
        </head>
        <body style='margin: 0; padding: 0; font-family: Georgia, serif; background-color: #f4f4f4;'>
            <div style='max-width: 600px; margin: 0 auto; background-color: white; box-shadow: 0 0 20px rgba(0,0,0,0.1);'>
                <!-- Header -->
                <div style='background: linear-gradient(135deg, {$cor['primary']} 0%, " . $this->darkenColor($cor['primary'], 20) . " 100%); padding: 30px; text-align: center; color: white;'>
                    <h1 style='margin: 0; font-size: 28px; font-weight: bold;'>🌿 Espaço de Biodiversidade FSA</h1>
                    <p style='margin: 10px 0 0 0; font-size: 16px; opacity: 0.9;'>Sistema de Agendamentos</p>
                </div>
                
                <!-- Content -->
                <div style='padding: 30px;'>
                    <div style='background: {$cor['bg']}; padding: 25px; border-radius: 10px; border-left: 5px solid {$cor['primary']}; margin-bottom: 25px;'>
                        <h2 style='margin: 0 0 15px 0; color: {$cor['primary']}; font-size: 24px;'>$titulo</h2>
                        <p style='margin: 0; font-size: 16px; color: #333;'>Olá, <strong>$nome</strong>!</p>
                    </div>
                    
                    <p style='font-size: 17px; line-height: 1.6; color: #333; margin: 20px 0;'>$mensagemPrincipal</p>
                    
                    $detalhesHTML
                    
                    $instrucoesHTML
                </div>
                
                <!-- Footer -->
                <div style='background: #f8f9fa; padding: 25px; border-top: 1px solid #dee2e6; text-align: center;'>
                    <p style='margin: 0 0 10px 0; font-size: 16px; color: #666;'>
                        <strong>Centro Universitário Fundação Santo André</strong>
                    </p>
                    <p style='margin: 0 0 15px 0; font-size: 14px; color: #888;'>
                        Espaço de Biodiversidade - Promovendo a educação ambiental
                    </p>
                    <div style='border-top: 1px solid #dee2e6; padding-top: 15px; margin-top: 15px;'>
                        <p style='margin: 0; font-size: 12px; color: #999;'>
                            Este é um email automático. Por favor, não responda diretamente a esta mensagem.
                        </p>
                    </div>
                </div>
            </div>
        </body>
        </html>";
    }
    
    private function darkenColor($hex, $percent) {
        $hex = str_replace('#', '', $hex);
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        $r = max(0, min(255, $r - ($r * $percent / 100)));
        $g = max(0, min(255, $g - ($g * $percent / 100)));
        $b = max(0, min(255, $b - ($b * $percent / 100)));
        
        return sprintf("#%02x%02x%02x", $r, $g, $b);
    }
}
?>