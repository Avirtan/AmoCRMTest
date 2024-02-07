export

compose-up: 
	docker compose --env-file ./backend/.env  up
.PHONY: compose-up

