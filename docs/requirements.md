# Estrutura do Sistema

O sistema terá usuários com autenticação via login para acesso seguro. Após o login, o usuário será direcionado a um dashboard com informações resumidas sobre seu progresso e acesso rápido às funcionalidades principais.

## Cadastro de Tradução

* O usuário escolhe o livro bíblico a ser traduzido a partir de uma lista que exibe:  
  * Nome do livro  
  * Porcentagem do trabalho concluído (calculada com base na quantidade total de itens a traduzir e os itens já finalizados)

* Ao selecionar um livro, o sistema apresenta a lista de capítulos daquele livro, mostrando em cada um:  
  * Identificação (exemplo: "Mateus 1")  
  * Quantidade total de versículos no capítulo  
  * Quantidade de versículos traduzidos  
  * Porcentagem de conclusão do capítulo

## Visualização e Tradução dos Versículos

* Ao abrir um capítulo, o usuário visualiza a lista de versículos contendo:  
  * Identificação do versículo (exemplo: "Mateus 1:1")  
  * Texto original do versículo em português (exemplo: "Livro da genealogia de Jesus Cristo, filho de David, filho de Abraão.")  
* Ao selecionar um versículo, são exibidas as informações para auxiliar na tradução:  
  * Apresentação destacada e visível do livro, capítulo e versículo  
  * Texto em grego original do versículo  
  * Formulário para entrada da tradução, com campo de texto e botão para salvar a tradução  
  * Lista das referências cadastradas para o versículo atual, com cada item contendo:  
    * Vocábulo referenciado  
    * Texto de referência  
    * Botões para editar ou adicionar novo vocábulo referente ao versículo

## Guias (Tabs) Funcionais para Assistência na Tradução

* Primeira guia: "Capítulo original"  
  * Exibe o capítulo completo em uma tabela com três colunas:  
    * Texto em grego original  
    * Tradução Almeida  
    * Nova tradução sendo construída pelo usuário

* Guias adicionais para cada palavra em grego do versículo atual, contendo:  
  * Palavra em grego original  
  * Código STRONG (referência James Strong) acessível com link para a fonte [https://search.nepebrasil.org/strongs/](https://search.nepebrasil.org/strongs/)  
  * Listagem de todas as ocorrências dessa palavra em toda a Bíblia, apresentadas com:  
    * Referência do versículo com link direto para consulta  
    * Texto interlinear contendo a palavra grega, o termo strong e a tradução  
    * Visualização da palavra em grego no contexto do versículo original, apresentando adequadamente os elementos destacados em diferentes colunas, como no exemplo fornecido

A interface para as ocorrências deverá ser organizada em blocos que mostrem claramente as referências, o texto traduzido e o grego original para facilitar a comparação e estudo aprofundado da palavra.

## Cadastro de referências do versículo

Quando for cadastrar uma referência do versículo, o sistema exibe as informações do versículo, no idioma original, a tradução do Almeida e o usuário deve informar o Vocábulo e o Texto da Referência.

### Auxilio 

No cadastro de referência do versículo, o sistema exibirá, a princípio, uma lista das palavras no idioma original. O usuário vai escolher uma das palavras da lista e clicar em ok.  
Quando fizer isso, automaticamente cadastrará os dados do texto de referência entendo, em negrito, a palavra no idioma original, depois, um hifem, depois o texto strong.

## Cadastro de referências Globais

Quando for cadastrar uma referência global, o usuário deve informar o Vocábulo e o Texto da Referência.


# Sobre a tradução
A tradução atual é da versão do Haroldo Dutra. Dessa forma, toda vez que o usuário for cadastrar, editar ou visualizar uma tradução, o sistema irá editar um registro de verse_text (gerando um histórico de versículos traduzidos) irá editar um registro com o version_id = 17 ((HD) - Haroldo Dutra).
Quando falamos da tradução do almeida, ela é a version_id = 1.
Agora, quando falamos do texto original, se for o novo testamento, ele é o version_id = 18 ((BGB) - Bíblia Grega Bereana), mas se for do velho testamento, ele é o version_id = 19 (HSB) Hebrew Study Bible.
A porcentagem de conclusão de livros e capítulos é calculada com base na quantidade total de versículos no livro ou capítulo e a quantidade de versículos já traduzidos, ou seja, existentes em  version_id = 17 ((HD) - Haroldo Dutra).