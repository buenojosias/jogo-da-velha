# Fundação do Game Engine — Etapa 3.1

O domínio inicial está em `app/Games` e usa somente recursos nativos de PHP. Nenhuma classe depende de Livewire, Eloquent, HTTP, Blade ou JavaScript. Não há implementação de modalidade nem persistência.

## Objetos e contrato

| Tipo | Responsabilidade |
| --- | --- |
| `PlayerMark` | Identifica os lados X e O. |
| `ParticipantKind`, `Participant`, `Participants` | Identificam visitante, usuário ou bot por tipo e identificador; associam cada participante a X ou O. `User` é apenas um tipo de identidade, sem ligação com o model Laravel. |
| `Modality` | Guarda uma chave e uma configuração própria da modalidade. |
| `MatchStatus` | Representa aguardando, em andamento, concluída com vencedor ou empatada. |
| `MatchState` | Guarda modalidade, participantes, situação, jogador do próximo turno, dados próprios da modalidade e eventual vencedor. Impede combinações incoerentes de situação, turno e vencedor. `isFinished()` informa se a partida terminou. |
| `GameAction` | Guarda lado que age, tipo da ação e payload específico. O tipo não se limita à colocação; movimento, seleção e rolagem cabem no mesmo envelope. |
| `ActionValidation` | Informa se a ação é válida ou traz um código de rejeição sem texto de interface. |
| `ActionResult` | Retorna a validação e o estado resultante. Uma rejeição recebe e devolve o mesmo objeto de estado. |
| `GameRules` | Contrato para criar estado inicial, validar uma ação e aplicá-la, devolvendo `ActionResult`. |

`MatchState`, `GameAction` e os demais objetos de valor são `readonly`. Implementações futuras de `GameRules` devem devolver uma nova instância de `MatchState` ao aceitar uma ação e usar `ActionResult::rejected($state, $reason)` ao rejeitá-la. Assim, uma ação inválida preserva o estado recebido. O estado final informa vitória ou empate por `status` e `winner`; placares específicos poderão residir nos dados da modalidade.

As estruturas `configuration`, `data` e `payload` são arrays associativos porque os formatos concretos ainda dependem da modalidade. As regras de cada modalidade deverão validar suas chaves e valores. Esta etapa não define serialização, esquema de persistência nem transporte entre processos.

## Uso previsto

Uma implementação futura de `GameRules` receberá `Modality`, `Participants` e o lado inicial em `initialState()`. A aplicação enviará uma `GameAction` à regra, poderá consultar `validate()` e receberá de `apply()` um `ActionResult`. O chamador lerá `result->state->status`, `winner` e `isFinished()` para apresentar o resultado no meio apropriado. O domínio não produz HTML ou mensagens para o usuário.

Esse limite permite usar a mesma implementação de regras em uma partida local, contra bot ou multiplayer: a origem dos participantes e o canal de entrada/saída ficam fora do motor. A associação entre uma identidade autenticada/visitante e o `PlayerMark` autorizado também fica a cargo da camada que chamar o motor.

## Testes e próximos pontos de decisão

`tests/Unit/Games` cobre os 21 cenários dos objetos: construção válida, participantes distintos, identificação X/O, ações de formatos diferentes, quatro situações da partida, invariantes de turno/vencedor e resultado aceito/rejeitado. Resultado: **21 testes e 49 asserções aprovados**. Nenhum teste de regra de vitória foi criado.

Antes da etapa 3.2 será necessário decidir, para a primeira modalidade escolhida, o esquema concreto de `configuration`, `data` e `payload`, os códigos de rejeição e a política de sorteio/entrada de aleatoriedade. Também será preciso definir em qual camada ocorre a verificação da identidade que solicita a ação. Essas escolhas foram deixadas fora dos objetos fundamentais para evitar contratos sem caso de uso implementado.
