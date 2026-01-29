<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <title><?= $tituloRelatorio ?></title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }

        h4 {
            margin: 5px 0;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .table th,
        .table td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }

        .table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .header {
            margin-bottom: 20px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 10px;
        }

        .total-row {
            font-weight: bold;
            background-color: #eee;
        }

        .badge {
            padding: 2px 5px;
            border-radius: 3px;
            color: #fff;
            font-size: 10px;
        }
    </style>
</head>

<body>

    <div class="header">
        <table style="width: 100%; border: none;">
            <tr>
                <td style="width: 20%; border: none;">
                <td style="width: 20%; border: none;">
                    <?php if (!empty($logoBase64)): ?>
                        <img src="<?= $logoBase64 ?>" style="max-width: 100px;">
                    <?php elseif (!empty($emitente['url_logo'])): ?>
                        <!-- Fallback (imagem externa ou se base64 falhar) -->
                        <img src="<?= MAPOS_URL ?>assets/uploads/<?= $emitente['url_logo'] ?>" style="max-width: 100px;">
                    <?php endif; ?>
                </td>
                </td>
                <td style="width: 80%; border: none; text-align: center;">
                    <h3><?= $emitente['nome'] ?? 'Map-OS' ?></h3>
                    <p>
                        <?= $emitente['cnpj'] ? 'CNPJ: ' . $emitente['cnpj'] . ' - ' : '' ?>
                        <?= $emitente['rua'] ? $emitente['rua'] . ', ' . $emitente['numero'] . ' - ' : '' ?>
                        <?= $emitente['bairro'] ? $emitente['bairro'] . ' - ' : '' ?>
                        <?= $emitente['cidade'] ? $emitente['cidade'] . '/' . $emitente['uf'] : '' ?> <br>
                        <?= $emitente['telefone'] ? 'Fone: ' . $emitente['telefone'] . ' - ' : '' ?>
                        <?= $emitente['email'] ? 'Email: ' . $emitente['email'] : '' ?>
                    </p>
                </td>
            </tr>
        </table>
    </div>

    <h3 class="text-center"><?= $tituloRelatorio ?></h3>

    <?php if (!empty($filtrosDescricao)): ?>
        <p style="font-size: 11px; color: #555;">
            <strong>Filtros:</strong> <?= implode(' | ', $filtrosDescricao) ?>
        </p>
    <?php endif; ?>

    <?php if ($tipo == 'estatistica'): ?>

        <!-- ================= ESTATÍSTICAS ================= -->
        <table class="table">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th class="text-center">Quantidade de Orçamentos</th>
                    <th class="text-right">Valor Total</th>
                    <th class="text-right">Ticket Médio</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($estatisticas)): ?>
                    <tr>
                        <td colspan="4" class="text-center">Nenhum registro encontrado.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($estatisticas as $est):
                        $ticketMedio = $est['total'] > 0 ? $est['valor_total'] / $est['total'] : 0;
                        ?>
                        <tr>
                            <td><?= $est['nomeCliente'] ?></td>
                            <td class="text-center"><?= $est['total'] ?></td>
                            <td class="text-right">R$ <?= number_format($est['valor_total'], 2, ',', '.') ?></td>
                            <td class="text-right">R$ <?= number_format($ticketMedio, 2, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

    <?php else: ?>

        <!-- ================= LISTAGEM GERAL ================= -->
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 30px;" class="text-center">#</th>
                    <th>Cliente</th>
                    <th style="width: 80px;" class="text-center">Data</th>
                    <th style="width: 90px;" class="text-center">Status</th>
                    <th style="width: 100px;" class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $totalGeral = 0;
                if (empty($orcamentos)): ?>
                    <tr>
                        <td colspan="5" class="text-center">Nenhum orçamento encontrado.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orcamentos as $o):
                        $totalGeral += $o['valor_total'];
                        $dataFormatada = date('d/m/Y', strtotime($o['data_criacao']));
                        ?>
                        <tr>
                            <td class="text-center"><?= $o['id'] ?></td>
                            <td><?= $o['nomeCliente'] ?></td>
                            <td class="text-center"><?= $dataFormatada ?></td>
                            <td class="text-center"><?= $o['status'] ?></td>
                            <td class="text-right">R$ <?= number_format($o['valor_total'], 2, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td colspan="4" class="text-right">TOTAL GERAL:</td>
                        <td class="text-right">R$ <?= number_format($totalGeral, 2, ',', '.') ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

    <?php endif; ?>

    <div style="margin-top: 20px; font-size: 10px; text-align: right; color: #777;">
        Gerado em: <?= date('d/m/Y H:i:s') ?> pelo Módulo de Orçamentos
    </div>

</body>

</html>