up:
	docker compose up -d
exec:
	docker compose exec --user=laradock workspace bash
build:
	docker compose up -d --build $(c)
	@if [ -z "$(c)" ]; then make up; fi
# make build c=mariadb   # білд конкретного контейнера
# make build             # білд всіх + up
stop:
	docker compose stop
down:
	docker compose down
restart:
	@make down
	@make up
destroy:
	docker compose down --rmi all --volumes
destroy-volumes:
	docker compose down --volumes
ps:
	docker compose ps
logs:
	docker compose logs
