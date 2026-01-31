# 🗺️ Como Adicionar o Botão "Orçamentos" no Menu do MapOS

Este arquivo serve como **backup**. Se você atualizar o MapOS, o menu lateral será resetado e o botão do módulo sumirá. Siga os passos abaixo para colocá-lo de volta.

---

### 📂 1. Arquivo para Editar
Vá até a pasta do MapOS e abra este arquivo:
`mapos/application/views/tema/menu.php`

---

### 📝 2. Código para Copiar
Copie exatamente o código abaixo:

```php
                <!-- Botão Módulo Orçamentos (Novo) -->
                <li class="<?= (strpos($_SERVER['REQUEST_URI'], 'modulos/orcamentos') !== false) ? 'active' : '' ?>">
                    <a class="tip-bottom" title="" href="<?= base_url() ?>modulos/orcamentos">
                        <i class='bx bx-file-blank iconX'></i>
                        <span class="title">Orçamentos (Novo)</span>
                        <span class="title-tooltip">Orçamentos</span>
                    </a>
                </li>
```

---

### 📍 3. Onde Colar
1. Dentro do arquivo `menu.php`, procure o menu de **Vendas** ou **Ordens de Serviço**.
   - Dica: Use `Ctrl+F` e procure pela palavra `Vendas` ou pelo termo `vOs`.
2. Cole o código logo abaixo do bloco PHP de encerramento `<?php } ?>` do menu que você encontrou.

**Exemplo Visual:**
```php
    ...
    <span class="title">Vendas</span>
    </a>
</li>
<?php } ?>
   <--- COLE AQUI --->
```

### ✅ 4. Salvar
Salve o arquivo e recarregue a página do MapOS. O botão deve aparecer!
