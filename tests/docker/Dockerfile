FROM alpine:latest

RUN apk --update add --no-cache openssh bash git rsync \
    && rm -rf /var/cache/apk/*

RUN ssh-keygen -A

COPY ssh_key.pub /root/.ssh/authorized_keys

EXPOSE 22
CMD ["/usr/sbin/sshd", "-D"]
