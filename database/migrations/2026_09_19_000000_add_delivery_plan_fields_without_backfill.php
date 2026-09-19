<?php

// Disponibiliza a classe base usada pelas migrations do Laravel.
use Illuminate\Database\Migrations\Migration;

// Disponibiliza os métodos usados para criar colunas e chaves.
use Illuminate\Database\Schema\Blueprint;

// Disponibiliza o acesso à estrutura atual do banco.
use Illuminate\Support\Facades\Schema;

/**
 * Completa a estrutura necessária para as entregas fracionadas.
 *
 * Esta migration apenas adiciona campos opcionais. Ela não percorre registros,
 * não converte cargas antigas, não cria entregas e não exclui dados.
 *
 * Os registros atuais recebem NULL nos campos novos e continuam no modelo antigo.
 */
class AddDeliveryPlanFieldsWithoutBackfill extends Migration
{
    // Executa as alterações quando a migration é aplicada.
    public function up()
    {
        // Confirma que a migration anterior criou a tabela de planejamentos.
        if (!Schema::hasTable('order_product_delivery_plans')) {
            // Interrompe a execução para não deixar a estrutura incompleta.
            throw new \RuntimeException('A tabela order_product_delivery_plans precisa existir antes desta migration.');
        }

        // Confirma que a tabela antiga de itens de carga está disponível.
        if (!Schema::hasTable('load_items')) {
            // Interrompe a execução porque o relacionamento depende dessa tabela.
            throw new \RuntimeException('A tabela load_items precisa existir antes desta migration.');
        }

        // Evita tentar criar novamente a coluna caso ela já exista.
        if (!Schema::hasColumn('order_product_delivery_plans', 'carga')) {
            // Abre a tabela de planejamentos para adicionar a composição dos paletes.
            Schema::table('order_product_delivery_plans', function (Blueprint $table) {
                // Cria o campo que guarda a composição dos paletes em JSON.
                $table->text('carga')
                    // Permite que planejamentos antigos permaneçam sem composição.
                    ->nullable()
                    // Posiciona o campo depois da data para facilitar a leitura da tabela.
                    ->after('delivery_date');
            });
        }

        // Evita tentar criar novamente o vínculo caso ele já exista.
        if (!Schema::hasColumn('load_items', 'delivery_plan_id')) {
            // Abre a tabela de itens de carga para relacionar uma entrega.
            Schema::table('load_items', function (Blueprint $table) {
                // Cria o campo que identifica a entrega planejada.
                $table->foreignId('delivery_plan_id')
                    // Mantém os itens antigos sem obrigar um planejamento.
                    ->nullable()
                    // Posiciona o vínculo depois do produto do pedido.
                    ->after('order_product_id')
                    // Cria a chave estrangeira para a tabela de planejamentos.
                    ->constrained('order_product_delivery_plans')
                    // Preserva o item da carga se o planejamento for removido.
                    ->onDelete('set null');
            });
        }
    }

    // Define o comportamento quando a migration é revertida.
    public function down()
    {
        // Não remove os campos para evitar perda de dados já cadastrados.
    }
}
