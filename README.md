##The package.json configuration
Set whatever plugins you'll need for the cordova app that will be a default when apps are generated.

##How to install:
1. Pull the project to the server side or local docker container - depends on whet you need
2. Assuming that your PWA project will be set on a subdomain wildcard or a single domain,
   You'll need to set the following environment variables
   `GENERATOR_DOMAIN_TYPE` - can contain `wildcard` or `single`, the `single` value is set as the default value if you target PWA with a single domain use 
   `GENERATOR_DOMAIN_NAME` - will be the default domain if the type is set to be a wildcard

##How to use the generated apps:
1. Generate a new application with an appropriate namespace (ie com.example.app)
2. Extract the generated contents into your empty application directory 
3. Run npm i
4. Add cordova platform that you need with Cordova CLI
