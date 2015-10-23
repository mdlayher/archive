make:
	go build -o bin/tcparity

fmt:
	go fmt
	golint .
	errcheck github.com/mdlayher/tcparity

darwin_386:
	GOOS="darwin" GOARCH="386" go build -o bin/tcparity_darwin_386

darwin_amd64:
	GOOS="darwin" GOARCH="amd64" go build -o bin/tcparity_darwin_amd64

linux_386:
	GOOS="linux" GOARCH="386" go build -o bin/tcparity_linux_386

linux_amd64:
	GOOS="linux" GOARCH="amd64" go build -o bin/tcparity_linux_amd64
