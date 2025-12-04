# Layout de Diagramação Bíblica

Este documento descreve as regras de layout para a visualização de impressão da Bíblia.

## Estrutura Geral
- **Fonte**: Times New Roman (ou família Serif similar).
- **Colunas**: Texto dividido em duas colunas.
- **Divisor**: Uma linha vertical bem marcada dividindo as duas colunas.

## Cabeçalho
- **Nome do Livro**: Deve aparecer no início, com fonte bem grande (ex: 3rem ou 4rem).

## Texto Bíblico
- **Capítulos**:
    - O número do capítulo deve ser grande, ocupando a altura de 2 linhas de texto normal (Drop Cap).
- **Versículos**:
    - Cada versículo deve iniciar em uma nova linha.
    - **Versículo 1**: Não deve possuir número exibido.
    - **Versículo 2 em diante**: Deve exibir o número do versículo antes do texto.
        - Estilo: Negrito, fonte menor que o texto bíblico.
- **Assuntos (Títulos)**:
    - Devem vir em itálico.
    - Fonte um pouco maior do que o texto bíblico.

## Referências Cruzadas
O sistema deve compilar Referências Globais e Específicas.

### Numeração
- Código numérico crescente, iniciando em 1.
- A numeração reinicia a cada novo capítulo.
- **Ordem**: A numeração deve seguir estritamente a ordem de aparição das referências no texto bíblico.

### No Texto
- O código numérico deve aparecer **antes** da palavra ou termo referenciado.
- **Estilo**: Sobrescrito e itálico.
- **Lógica de Vínculo**: O sistema deve buscar o termo no versículo. Se não encontrar, vincula à primeira palavra do versículo.

### Rodapé (Notas de Fim de Página)
- As referências devem ser exibidas no rodapé da página onde o texto aparece.
- **Formato**: `Código Versículo Texto` (Tudo na mesma linha).
    - **Código**: Texto em itálico, sobrescrito.
    - **Versículo**: `Capítulo:Versículo` em negrito.
    - **Texto**: O texto da referência.

## Observações Técnicas
- A paginação deve ser dinâmica para garantir que as referências no rodapé correspondam exatamente aos versículos exibidos naquela página.
