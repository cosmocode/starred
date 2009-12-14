CREATE TABLE stars (
    pid   PRIMARY KEY,
    login,
    stardate INTEGER
);

CREATE UNIQUE INDEX idx_stars_pid ON stars(pid);
CREATE INDEX idx_stars_login ON stars(login);
