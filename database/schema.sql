CREATE TABLE IF NOT EXISTS identificacoes_pedido (
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 nome TEXT NOT NULL,
 tipo_pedido TEXT NOT NULL CHECK(tipo_pedido IN ('entrega','retirada')),
 endereco TEXT,
 forma_pagamento TEXT NOT NULL CHECK(forma_pagamento IN ('dinheiro','pix','credito','debito')),
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CHECK ((tipo_pedido='entrega' AND endereco IS NOT NULL AND length(trim(endereco))>0) OR (tipo_pedido='retirada' AND endereco IS NULL))
);
