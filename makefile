# Adapted from https://stackoverflow.com/a/14061796
# If the first argument is "composer"...
ifeq (composer,$(firstword $(MAKECMDGOALS)))
  # use the rest as arguments for "composer"
  COMPOSER_ARGS := $(wordlist 2,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS))
  # ...and turn them into do-nothing targets
  $(eval $(COMPOSER_ARGS):;@:)
endif

composer:
	COMPOSER=composer.json composer $(COMPOSER_ARGS)


composer-dev:
	COMPOSER=composer.dev.json composer $(COMPOSER_ARGS)
