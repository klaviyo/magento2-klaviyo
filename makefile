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

# If the first argument is "composer-dev"...
ifeq (composer-dev,$(firstword $(MAKECMDGOALS)))
  # use the rest as arguments for "composer"
  COMPOSER_DEV_ARGS := $(wordlist 2,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS))
  # ...and turn them into do-nothing targets
  $(eval $(COMPOSER_DEC_ARGS):;@:)
endif

composer-dev:
	COMPOSER=composer.dev.json composer $(COMPOSER_DEV_ARGS)
