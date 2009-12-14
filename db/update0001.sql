CREATE TABLE stars (
    pid,
    login,
    stardate INTEGER
);

CREATE UNIQUE INDEX idx_stars_pid_login ON stars(pid, login);
