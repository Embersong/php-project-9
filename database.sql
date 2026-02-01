CREATE TABLE IF NOT EXISTS urls
(
    id         INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name       VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT NOW() NOT NULL
);

CREATE TABLE IF NOT EXISTS url_checks
(
    id          INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    url_id      INTEGER       NOT NULL REFERENCES urls (id) ON DELETE CASCADE,
    status_code INTEGER       NULL,
    h1          VARCHAR(1024) NULL,
    title       TEXT          NULL,
    description TEXT          NULL,
    created_at  TIMESTAMP DEFAULT NOW()   NOT NULL
);