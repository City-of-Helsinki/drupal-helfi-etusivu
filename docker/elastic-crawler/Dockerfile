# The upstream image bundles a jlink-trimmed JRE that omits the `jdk.net`
# module. Apache HttpClient5 (used by the crawler to make HTTP requests)
# references `jdk.net.Sockets` for extended socket options, which crashes
# every request with:
#
#   java.lang.NoClassDefFoundError: jdk/net/Sockets
#   java.lang.ExceptionInInitializerError: ... DefaultHttpClientConnectionOperator
#
# Confirmed present in at least crawler:0.4.2 and crawler:1.0.1 (`java
# --list-modules` omits jdk.net in both). Installing a full JRE from the
# base image's own Chainguard apk repos and repointing JAVA_HOME at it
# works around this without touching the app code.
FROM docker.elastic.co/integrations/crawler:1.0.1

USER root
RUN apk update && apk add --no-cache openjdk-21-jre && apk cache clean

ENV JAVA_HOME=/usr/lib/jvm/java-21-openjdk
ENV PATH="${JAVA_HOME}/bin:${PATH}"

USER java
