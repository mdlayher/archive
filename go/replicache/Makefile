make:
	go build -o bin/replicache

fmt:
	go fmt
	golint .
	errcheck github.com/mdlayher/replicache

test:
	go test github.com/mdlayher/replicache

darwin_386:
	GOOS="darwin" GOARCH="386" go build -o bin/replicache_darwin_386

darwin_amd64:
	GOOS="darwin" GOARCH="amd64" go build -o bin/replicache_darwin_amd64

linux_386:
	GOOS="linux" GOARCH="386" go build -o bin/replicache_linux_386

linux_amd64:
	GOOS="linux" GOARCH="amd64" go build -o bin/replicache_linux_amd64

windows_386:
	GOOS="windows" GOARCH="386" go build -o bin/replicache_windows_386.exe

windows_amd64:
	GOOS="windows" GOARCH="amd64" go build -o bin/replicache_windows_amd64.exe
