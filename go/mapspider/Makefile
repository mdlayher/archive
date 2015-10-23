make:
	go build github.com/mdlayher/godigraph
	go build -o bin/mapspider

fmt:
	go fmt
	golint .
	errcheck github.com/mdlayher/mapspider

darwin_386:
	GOOS="darwin" GOARCH="386" go build -o bin/mapspider_darwin_386

darwin_amd64:
	GOOS="darwin" GOARCH="amd64" go build -o bin/mapspider_darwin_amd64

linux_386:
	GOOS="linux" GOARCH="386" go build -o bin/mapspider_linux_386

linux_amd64:
	GOOS="linux" GOARCH="amd64" go build -o bin/mapspider_linux_amd64
